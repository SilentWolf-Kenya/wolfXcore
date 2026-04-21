#!/bin/bash
# deploy-bot-stability.sh
# One-shot deployer for Task #1 (Bot Stability & Session Safety).
# Runs FROM the Replit workspace, pushes everything to the VPS, then runs migrations + cache clears.
# Idempotent — safe to re-run.

set -euo pipefail

: "${VPS_HOST:?VPS_HOST not set}"
: "${VPS_PASSWORD:?VPS_PASSWORD not set}"

REMOTE=/var/www/wolfxcore
SSH_OPTS="-o StrictHostKeyChecking=no -o ConnectTimeout=15"
SSHPASS_CMD="sshpass -p $VPS_PASSWORD"

run_ssh() { $SSHPASS_CMD ssh $SSH_OPTS root@"$VPS_HOST" "$@"; }
run_scp() { $SSHPASS_CMD scp $SSH_OPTS "$@"; }

echo "═══ 1. Pushing PHP source ═══"
run_scp app/Services/ServerProvisioningService.php                       root@$VPS_HOST:$REMOTE/app/Services/
run_scp app/Models/WxnBotHealth.php                                      root@$VPS_HOST:$REMOTE/app/Models/
run_scp app/Http/Controllers/Api/Client/Servers/PowerController.php      root@$VPS_HOST:$REMOTE/app/Http/Controllers/Api/Client/Servers/
run_scp app/Http/Controllers/Api/Remote/ActivityProcessingController.php root@$VPS_HOST:$REMOTE/app/Http/Controllers/Api/Remote/
run_scp app/Http/Controllers/Admin/SuperAdminController.php              root@$VPS_HOST:$REMOTE/app/Http/Controllers/Admin/
run_scp app/Http/Controllers/BotController.php                           root@$VPS_HOST:$REMOTE/app/Http/Controllers/
run_scp app/Transformers/Api/Client/ServerTransformer.php                root@$VPS_HOST:$REMOTE/app/Transformers/Api/Client/
run_scp app/Providers/RouteServiceProvider.php                           root@$VPS_HOST:$REMOTE/app/Providers/
run_scp database/migrations/2026_04_17_000001_create_wxn_bot_health_table.php   root@$VPS_HOST:$REMOTE/database/migrations/
run_scp database/migrations/2026_04_17_000002_create_wxn_bot_crashes_table.php  root@$VPS_HOST:$REMOTE/database/migrations/
run_scp database/migrations/2026_04_17_000003_backfill_auto_update_disabled.php root@$VPS_HOST:$REMOTE/database/migrations/
run_scp resources/views/admin/super/bot_health.blade.php                 root@$VPS_HOST:$REMOTE/resources/views/admin/super/

echo "═══ 1b. Pushing React frontend changes (if built locally) ═══"
run_scp resources/scripts/api/server/getServer.ts                            root@$VPS_HOST:$REMOTE/resources/scripts/api/server/
run_scp resources/scripts/components/server/console/ServerConsoleContainer.tsx root@$VPS_HOST:$REMOTE/resources/scripts/components/server/console/
# Ship rebuilt bundle if present locally — never build on the VPS.
if [ -d public/assets ]; then
  echo "(syncing public/assets — make sure you ran 'yarn build:production' first)"
  $SSHPASS_CMD rsync -az -e "ssh $SSH_OPTS" public/assets/ root@$VPS_HOST:$REMOTE/public/assets/
fi

echo "═══ 2. Pushing maintenance scripts ═══"
run_scp scripts/wolfxcore-session-backup.sh   root@$VPS_HOST:/usr/local/bin/wolfxcore-session-backup.sh
run_scp scripts/wolfxcore-session-restore.sh  root@$VPS_HOST:/usr/local/bin/wolfxcore-session-restore.sh

# Sudoers rule: allow www-data (panel/php-fpm) to invoke the per-UUID restore hook
# from PowerController without a password. The script validates the UUID pattern
# strictly before doing anything, and the sudoers entry is restricted to that one
# binary path — no general sudo escalation.
run_ssh "cat > /etc/sudoers.d/wolfxcore-session-restore <<'SUDO'
www-data ALL=(root) NOPASSWD: /usr/local/bin/wolfxcore-session-restore.sh
SUDO
chmod 440 /etc/sudoers.d/wolfxcore-session-restore
visudo -c -f /etc/sudoers.d/wolfxcore-session-restore >/dev/null"

echo "═══ 3. Configuring VPS-level fixes ═══"
# Pull DB credentials from the panel's own .env on the VPS — do NOT hardcode here.
# The deploy script reads /var/www/wolfxcore/.env at apply time and exports
# WXN_DB_USER / WXN_DB_PASS / WXN_DB_NAME for any DB-touching steps below.
run_ssh "set -e
. <(grep -E '^DB_(USERNAME|PASSWORD|DATABASE|HOST)=' $REMOTE/.env | sed 's/^DB_USERNAME=/WXN_DB_USER=/; s/^DB_PASSWORD=/WXN_DB_PASS=/; s/^DB_DATABASE=/WXN_DB_NAME=/; s/^DB_HOST=/WXN_DB_HOST=/')
export WXN_DB_USER WXN_DB_PASS WXN_DB_NAME WXN_DB_HOST
chmod +x /usr/local/bin/wolfxcore-session-backup.sh /usr/local/bin/wolfxcore-session-restore.sh

# Kernel VM tuning. NOTE on overcommit_memory:
#   * mode 0 (default): heuristic — kernel guesses, can deny large allocations randomly
#   * mode 1 (heuristic-always): never deny on the malloc path; OOM killer handles it
#   * mode 2 (strict): refuse any allocation past CommitLimit — caused total VPS lockdown
#                      on this box previously, do not enable.
# We pick mode 1 explicitly so node/baileys never gets a NULL malloc mid-session and the
# kernel OOM killer (with our oom_score_adj below) decides what dies.
cat > /etc/sysctl.d/99-wolfxcore.conf <<'EOF'
vm.swappiness = 10
vm.vfs_cache_pressure = 50
vm.dirty_background_ratio = 5
vm.dirty_ratio = 10
vm.overcommit_memory = 1
vm.overcommit_ratio = 80
EOF
sysctl --system >/dev/null 2>&1 | true

# Docker daemon defaults — log rotation + sane ulimits
mkdir -p /etc/docker
[ -f /etc/docker/daemon.json ] && cp /etc/docker/daemon.json /etc/docker/daemon.json.bak.\$(date +%s) || true
cat > /etc/docker/daemon.json <<'EOF'
{
  \"log-driver\": \"json-file\",
  \"log-opts\": { \"max-size\": \"10m\", \"max-file\": \"3\" },
  \"default-shm-size\": \"64M\",
  \"live-restore\": true,
  \"storage-driver\": \"overlay2\",
  \"default-ulimits\": {
    \"nofile\": { \"Name\": \"nofile\", \"Soft\": 4096, \"Hard\": 8192 },
    \"nproc\":  { \"Name\": \"nproc\",  \"Soft\": 512,  \"Hard\": 1024 }
  }
}
EOF
systemctl reload docker 2>/dev/null || echo 'docker reload skipped (live-restore handles config on next restart)'

# Ensure 8GB total swap is present. Strategy: leave any existing /swapfile-wolfx in place
# (probably 4GB from an earlier run) and add a second 4GB file so total = 8GB. We avoid
# resizing in-place because that requires swapoff which is risky under load.
total_swap_kb=\$(awk '/^SwapTotal:/ {print \$2}' /proc/meminfo)
target_kb=\$((8 * 1024 * 1024))
if [ \"\${total_swap_kb:-0}\" -lt \"\$target_kb\" ]; then
  if [ ! -f /swapfile-wolfx ]; then
    fallocate -l 4G /swapfile-wolfx 2>/dev/null || dd if=/dev/zero of=/swapfile-wolfx bs=1M count=4096 status=none
    chmod 600 /swapfile-wolfx
    mkswap /swapfile-wolfx >/dev/null
    swapon /swapfile-wolfx
    grep -q '/swapfile-wolfx' /etc/fstab || echo '/swapfile-wolfx none swap sw 0 0' >> /etc/fstab
  fi
  total_swap_kb=\$(awk '/^SwapTotal:/ {print \$2}' /proc/meminfo)
  if [ \"\${total_swap_kb:-0}\" -lt \"\$target_kb\" ] && [ ! -f /swapfile-wolfx2 ]; then
    fallocate -l 4G /swapfile-wolfx2 2>/dev/null || dd if=/dev/zero of=/swapfile-wolfx2 bs=1M count=4096 status=none
    chmod 600 /swapfile-wolfx2
    mkswap /swapfile-wolfx2 >/dev/null
    swapon /swapfile-wolfx2
    grep -q '/swapfile-wolfx2' /etc/fstab || echo '/swapfile-wolfx2 none swap sw 0 0' >> /etc/fstab
  fi
fi

# OOM-score adjustments: tell the kernel to prefer killing bot containers over panel/system services.
# Wings + nginx + php-fpm get oom_score_adj=-500 (very unlikely to be killed); dockerd defaults stand.
for proc in wings nginx php-fpm; do
  for pid in \$(pgrep -f \"\$proc\" 2>/dev/null); do
    [ -w \"/proc/\$pid/oom_score_adj\" ] && echo -500 > \"/proc/\$pid/oom_score_adj\" 2>/dev/null || true
  done
done

# Ensure jq is installed (needed by session scripts)
command -v jq >/dev/null 2>&1 || apt-get install -y jq >/dev/null 2>&1 || true

# Cap wolfXcore-managed containers (UUID-named) and align DB metadata to match.
# Uses 'docker ps -a' so STOPPED containers get re-capped too — docker update works on
# stopped containers and the new limit takes effect on next start.
echo 'Capping wolfXcore containers (running + stopped)...'
uuid_re='^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}\$'
for c in \$(docker ps -a --format '{{.Names}}'); do
  echo \"\$c\" | grep -Eq \"\$uuid_re\" || continue
  lim=\$(docker inspect --format='{{.HostConfig.Memory}}' \$c 2>/dev/null)
  # Cap matches ServerProvisioningService policy (400MB RAM + 256MB swap → memory-swap=656m).
  # Re-cap if uncapped (0) OR if currently >400m (419430400 bytes). Don't shrink containers
  # that someone manually downsized below 400m.
  if [ \"\$lim\" = '0' ] || { [ \"\$lim\" != '0' ] && [ \"\${lim:-0}\" -gt 419430400 ]; }; then
    if docker update --memory=400m --memory-swap=656m --memory-reservation=200m \$c >/dev/null 2>&1; then
      echo \"  capped \$c (400m/256m swap)\"
      # Align panel DB row so committed-RAM math and admission control reflect reality.
      # Credentials come from /var/www/wolfxcore/.env (sourced above), never hardcoded.
      mysql -u \"\$WXN_DB_USER\" -p\"\$WXN_DB_PASS\" -h \"\${WXN_DB_HOST:-localhost}\" \"\$WXN_DB_NAME\" -e \"UPDATE servers SET memory=400, swap=256 WHERE uuid='\$c' AND (memory!=400 OR swap!=256);\" 2>/dev/null || true
    fi
  fi
done

# Install cron jobs for session snapshot/restore
cat > /etc/cron.d/wolfxcore-sessions <<'EOF'
# wolfXcore: snapshot Baileys auth folders every 5 minutes; restore corruption every minute
*/5 * * * * root /usr/local/bin/wolfxcore-session-backup.sh
*   * * * * root /usr/local/bin/wolfxcore-session-restore.sh
EOF
chmod 644 /etc/cron.d/wolfxcore-sessions
systemctl reload cron 2>/dev/null || service cron reload 2>/dev/null || true

# Create log files with correct perms
touch /var/log/wolfxcore-session-backup.log /var/log/wolfxcore-session-restore.log
chmod 640 /var/log/wolfxcore-session-*.log
"

echo "═══ 4. Running migrations + clearing caches ═══"
run_ssh "cd $REMOTE && \
  sudo -u www-data php artisan migrate --force && \
  sudo -u www-data php artisan config:clear && \
  sudo -u www-data php artisan view:clear && \
  sudo -u www-data php artisan route:clear && \
  sudo -u www-data php artisan optimize:clear"

echo "═══ 5. Verification ═══"
run_ssh "
echo '--- swap ---' && free -h | grep -E 'Mem|Swap'
echo '--- container caps ---'
unlim=\$(docker ps --format '{{.Names}}' | while read c; do l=\$(docker inspect --format='{{.HostConfig.Memory}}' \$c 2>/dev/null); [ \"\$l\" = '0' ] && echo \$c; done | wc -l)
echo \"unlimited containers remaining: \$unlim\"
echo '--- cron jobs ---'
ls -l /etc/cron.d/wolfxcore-sessions
echo '--- migration ---'
cd $REMOTE && sudo -u www-data php artisan migrate:status | grep -i bot_health || echo 'wxn_bot_health migration not found'
"

echo
echo "✅ DEPLOY COMPLETE."
echo "Visit https://panel.xwolf.space/admin/wxn-super/bot-health to see the dashboard."
