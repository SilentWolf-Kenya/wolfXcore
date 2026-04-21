#!/bin/bash
# vps-sync.sh
# Syncs the VPS panel directory (/var/www/wolfxcore) with the GitHub main branch.
#
# Problem: The VPS has locally modified files that block `git pull`. This script
# resolves that by stashing local changes, pulling the latest code, then running
# all post-pull steps (migrations, cache clears, queue restart).
#
# Usage (from Replit workspace):
#   VPS_HOST=161.97.100.158 VPS_PASSWORD=<pass> bash scripts/vps-sync.sh
#
# Usage (directly on the VPS as root):
#   bash /var/www/wolfxcore/scripts/vps-sync.sh --local
#
# Flags:
#   --local     Run entirely on the local machine (already on VPS, no SSH needed)
#   --reset     Hard-reset local changes instead of stashing them (loses local edits)
#   --dry-run   Show what would happen without making any changes
#
# Environment variables (only needed when NOT using --local):
#   VPS_HOST      IP or hostname of the VPS (default: 161.97.100.158)
#   VPS_PASSWORD  SSH password for root@VPS_HOST
#                 (Prefer SSH key auth — see note below)
#
# SSH key auth (recommended over password):
#   ssh-keygen -t ed25519 -f ~/.ssh/wolfxcore_deploy -N ""
#   ssh-copy-id -i ~/.ssh/wolfxcore_deploy.pub root@$VPS_HOST
#   Then: VPS_HOST=... SSH_KEY=~/.ssh/wolfxcore_deploy bash scripts/vps-sync.sh
#
# Prerequisites (on VPS):
#   git remote -v   # should show origin pointing to GitHub

set -euo pipefail

# ─── argument parsing ─────────────────────────────────────────────────────────
LOCAL_MODE=false
HARD_RESET=false
DRY_RUN=false
for arg in "$@"; do
  case "$arg" in
    --local)    LOCAL_MODE=true ;;
    --reset)    HARD_RESET=true ;;
    --dry-run)  DRY_RUN=true ;;
  esac
done

REMOTE_DIR=/var/www/wolfxcore
VPS_HOST="${VPS_HOST:-161.97.100.158}"

# ─── helpers ──────────────────────────────────────────────────────────────────
log()  { echo "[vps-sync] $*"; }
warn() { echo "[vps-sync] WARNING: $*" >&2; }
die()  { echo "[vps-sync] ERROR: $*" >&2; exit 1; }

# Emit command (or dry-run echo). Used in the outer (non-heredoc) scope.
dry_run_exec() {
  if $DRY_RUN; then
    echo "[DRY-RUN] would run: $*"
  else
    eval "$@"
  fi
}

# ─── SSH helper — prefers key auth, falls back to sshpass ─────────────────────
# SSH_OPTS deliberately avoids StrictHostKeyChecking=no; accept on first connect
# or use SSH_KNOWN_HOSTS_ACCEPT=yes to auto-accept once.
SSH_OPTS="-o ConnectTimeout=20 -o BatchMode=yes"
if [ "${SSH_KNOWN_HOSTS_ACCEPT:-}" = "yes" ]; then
  SSH_OPTS="$SSH_OPTS -o StrictHostKeyChecking=accept-new"
else
  SSH_OPTS="$SSH_OPTS -o StrictHostKeyChecking=yes"
fi

run_ssh() {
  local script="$1"
  if [ -n "${SSH_KEY:-}" ]; then
    ssh $SSH_OPTS -i "${SSH_KEY}" root@"${VPS_HOST}" bash -s <<< "$script"
  else
    # sshpass: write password to a temp file so it never appears in process list
    local tmp_pass
    tmp_pass=$(mktemp)
    printf '%s' "${VPS_PASSWORD}" > "$tmp_pass"
    chmod 600 "$tmp_pass"
    SSHPASS_FILE="$tmp_pass" sshpass -f "$tmp_pass" ssh \
      -o BatchMode=no $SSH_OPTS root@"${VPS_HOST}" bash -s <<< "$script"
    rm -f "$tmp_pass"
  fi
}

# ─── remote sync payload (heredoc — runs verbatim on VPS) ────────────────────
build_remote_script() {
  local hard_reset="$1"
  local dry_run="$2"
  cat <<REMOTE_SCRIPT
set -euo pipefail
REMOTE_DIR="${REMOTE_DIR}"
HARD_RESET="${hard_reset}"
DRY_RUN="${dry_run}"

log()  { echo "[vps] \$*"; }
warn() { echo "[vps] WARNING: \$*" >&2; }
die()  { echo "[vps] ERROR: \$*" >&2; exit 1; }

run() {
  if [ "\$DRY_RUN" = "true" ]; then
    echo "[DRY-RUN] \$*"
  else
    eval "\$@"
  fi
}

# fail-fast wrapper: runs a command and exits with a clear error if it fails
must_run() {
  local desc="\$1"; shift
  if [ "\$DRY_RUN" = "true" ]; then
    echo "[DRY-RUN] \$*"
    return 0
  fi
  if ! eval "\$@"; then
    die "\$desc failed (exit \$?). Halting — fix the error and re-run."
  fi
}

cd "\$REMOTE_DIR"

# ── 1. Confirm git remote points to GitHub ───────────────────────────────────
log "Checking git remote..."
origin_url=\$(git remote get-url origin 2>/dev/null || echo "")
if [ -z "\$origin_url" ]; then
  die "No 'origin' remote configured. Run: git remote add origin <github-url>"
fi
log "  origin → \$origin_url"

# ── 2. Show current status ────────────────────────────────────────────────────
log "Current git status:"
git status --short || true
changed_count=\$(git status --short | wc -l | tr -d ' ')

# ── 3. Handle local changes blocking pull ────────────────────────────────────
if [ "\$changed_count" -gt 0 ]; then
  if [ "\$HARD_RESET" = "true" ]; then
    warn "\$changed_count locally modified file(s) found — hard-resetting (changes will be LOST)."
    must_run "git checkout" git checkout -- .
    must_run "git clean"    git clean -fd
  else
    log "\$changed_count locally modified file(s) found — stashing them safely."
    stash_msg="vps-sync auto-stash \$(date '+%Y-%m-%d %H:%M:%S')"
    must_run "git stash" git stash push -m "\$stash_msg"
    log "  Stash saved. To recover: git stash pop"
  fi
else
  log "Working tree is clean — no stash needed."
fi

# ── 4. Fetch + pull (critical — fail fast on error) ──────────────────────────
log "Fetching from origin..."
must_run "git fetch" git fetch origin

current_branch=\$(git rev-parse --abbrev-ref HEAD)
log "On branch '\$current_branch'. Pulling origin/main..."

if [ "\$current_branch" != "main" ]; then
  warn "Branch is '\$current_branch', not 'main'. Switching to main."
  must_run "git checkout main" git checkout main
fi

must_run "git pull" git pull --ff-only origin main
log "  Pull complete."

# ── 5. Post-pull steps ────────────────────────────────────────────────────────

# composer install — critical: bad autoloader = white screen of death
log "Running composer install (no-dev)..."
if [ "\$DRY_RUN" = "true" ]; then
  echo "[DRY-RUN] composer install --no-dev --optimize-autoloader"
else
  composer_bin=""
  if command -v composer >/dev/null 2>&1; then
    composer_bin="composer"
  elif [ -f /usr/local/bin/composer ]; then
    composer_bin="php /usr/local/bin/composer"
  elif [ -f composer.phar ]; then
    composer_bin="php composer.phar"
  fi
  if [ -n "\$composer_bin" ]; then
    must_run "composer install" \
      \$composer_bin install --no-interaction --no-progress --no-dev --optimize-autoloader
  else
    warn "composer not found — skipping (run manually: composer install --no-dev)"
  fi
fi

# migrations — critical: missing schema = runtime errors
log "Running migrations..."
must_run "php artisan migrate" \
  sudo -u www-data php artisan migrate --force

# Cache clears — non-critical individually, but clear them all
log "Clearing Laravel caches..."
for cmd in config:clear view:clear route:clear optimize:clear event:clear; do
  if [ "\$DRY_RUN" = "true" ]; then
    echo "[DRY-RUN] php artisan \$cmd"
  else
    sudo -u www-data php artisan \$cmd 2>/dev/null || warn "php artisan \$cmd skipped"
  fi
done

# PHP-FPM reload — non-critical (old opcache will eventually expire)
log "Reloading PHP-FPM (if present)..."
if [ "\$DRY_RUN" != "true" ]; then
  if systemctl is-active --quiet php8.4-fpm 2>/dev/null; then
    systemctl reload php8.4-fpm && log "  php8.4-fpm reloaded"
  elif systemctl is-active --quiet php8.2-fpm 2>/dev/null; then
    systemctl reload php8.2-fpm && log "  php8.2-fpm reloaded"
  elif systemctl is-active --quiet php8.1-fpm 2>/dev/null; then
    systemctl reload php8.1-fpm && log "  php8.1-fpm reloaded"
  else
    warn "php-fpm service not found — you may need to reload it manually."
  fi
fi

# Queue restart — non-critical
log "Restarting queue workers..."
if [ "\$DRY_RUN" = "true" ]; then
  echo "[DRY-RUN] php artisan queue:restart"
else
  sudo -u www-data php artisan queue:restart 2>/dev/null \
    || warn "queue:restart skipped (no queue worker running?)"
fi

# ── Cron / Laravel scheduler — idempotent ─────────────────────────────────────
# Ensures "* * * * * php /var/www/wolfxcore/artisan schedule:run" is in root's
# crontab. Safe to run on every deploy — grep prevents duplicate entries.
CRON_ENTRY="* * * * * php \${REMOTE_DIR}/artisan schedule:run >> /dev/null 2>&1"
log "Checking Laravel scheduler cron entry..."
if [ "\$DRY_RUN" = "true" ]; then
  echo "[DRY-RUN] would ensure cron: \$CRON_ENTRY"
else
  if crontab -l 2>/dev/null | grep -qF "\${REMOTE_DIR}/artisan schedule:run"; then
    log "  Cron entry already present — no change needed."
  else
    ( crontab -l 2>/dev/null; echo "\$CRON_ENTRY" ) | crontab -
    log "  Cron entry added: \$CRON_ENTRY"
  fi
fi

# ── 6. Final status ───────────────────────────────────────────────────────────
log "Final git log (last 3 commits):"
git log --oneline -3

log "Final working-tree status:"
final_dirty=\$(git status --short | wc -l | tr -d ' ')
git status --short || true

if [ "\$final_dirty" -gt 0 ] && [ "\$DRY_RUN" != "true" ]; then
  warn "Working tree is not fully clean after sync (\$final_dirty file(s) changed)."
  warn "This may be normal (e.g. storage/ or .env) — inspect the diff above."
fi

echo ""
echo "✅ VPS sync complete."
REMOTE_SCRIPT
}

# ─── main ─────────────────────────────────────────────────────────────────────
if $LOCAL_MODE; then
  log "Running in --local mode (executing directly on this machine)."
  bash <(build_remote_script "$HARD_RESET" "$DRY_RUN")
else
  # Require either a key or a password
  if [ -z "${SSH_KEY:-}" ] && [ -z "${VPS_PASSWORD:-}" ]; then
    die "Provide SSH_KEY=<path-to-key> or VPS_PASSWORD=<pass>. Use --local if already on the VPS."
  fi

  log "Connecting to root@${VPS_HOST} to sync ${REMOTE_DIR}..."
  REMOTE_SCRIPT=$(build_remote_script "$HARD_RESET" "$DRY_RUN")

  if $DRY_RUN; then
    echo "[DRY-RUN] would SSH into root@${VPS_HOST} and run the sync script"
    echo "[DRY-RUN] script preview:"
    echo "---"
    echo "$REMOTE_SCRIPT"
  else
    run_ssh "$REMOTE_SCRIPT"
  fi
fi
