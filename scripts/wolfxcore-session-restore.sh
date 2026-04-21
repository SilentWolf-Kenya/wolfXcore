#!/bin/bash
# wolfxcore-session-restore.sh
# Detects corrupted/missing Baileys creds and restores from the latest valid snapshot.
# Install: copy to /usr/local/bin/, mark +x, schedule via cron every 1 minute.

set -u

VOLUMES_DIR="${VOLUMES_DIR:-/var/lib/wolfxcore/volumes}"
BACKUP_DIR="${BACKUP_DIR:-/var/backups/baileys-sessions}"
LOG="${LOG:-/var/log/wolfxcore-session-restore.log}"
WINGS_UID="${WINGS_UID:-988}"
WINGS_GID="${WINGS_GID:-988}"

# Optional: notify panel via curl on restore. Set PANEL_NOTIFY_URL+PANEL_TOKEN to enable.
PANEL_NOTIFY_URL="${PANEL_NOTIFY_URL:-}"
PANEL_TOKEN="${PANEL_TOKEN:-}"

# Per-UUID mode: when invoked with --uuid <uuid>, only check that one volume.
# Used as a deterministic pre-start hook from PowerController. Stays silent unless
# a restore actually happens, so it's cheap to call on every start.
TARGET_UUID=""
if [ "${1:-}" = "--uuid" ] && [ -n "${2:-}" ]; then
    TARGET_UUID="$2"
fi

exec >>"$LOG" 2>&1
ts() { date '+%Y-%m-%d %H:%M:%S'; }

[ -d "$VOLUMES_DIR" ] || exit 0
[ -d "$BACKUP_DIR" ] || exit 0

restored=0
checked=0

# Build the volume iteration list — either every volume (cron mode) or a single
# UUID (pre-start hook mode). UUID is validated against the strict wolfXcore
# UUID pattern to keep this safe to call from a web context.
if [ -n "$TARGET_UUID" ]; then
    if ! echo "$TARGET_UUID" | grep -Eq '^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$'; then
        echo "[$(ts)] refusing UUID with bad pattern: $TARGET_UUID"
        exit 0
    fi
    if [ ! -d "$VOLUMES_DIR/$TARGET_UUID" ]; then
        # Silent: bot may not have ever started yet; nothing to restore.
        exit 0
    fi
    iter_volumes=("$VOLUMES_DIR/$TARGET_UUID/")
else
    iter_volumes=("$VOLUMES_DIR"/*/)
fi

for vol in "${iter_volumes[@]}"; do
    uuid=$(basename "$vol")
    [ -d "$vol" ] || continue

    snap_dir="$BACKUP_DIR/$uuid"
    [ -d "$snap_dir" ] || continue

    latest=$(ls -t "$snap_dir"/snap-*.tar.gz 2>/dev/null | head -n 1)
    [ -z "$latest" ] && continue
    checked=$((checked+1))

    creds_file=$(find "$vol" -maxdepth 5 -name 'creds.json' -type f 2>/dev/null | head -n 1)

    needs_restore=0
    restore_reason=""
    if [ -z "$creds_file" ]; then
        # creds went missing — restore if snapshot has them
        if tar tzf "$latest" 2>/dev/null | grep -q 'creds.json'; then
            needs_restore=1
            restore_reason="creds.json missing"
        fi
    elif [ ! -s "$creds_file" ]; then
        needs_restore=1
        restore_reason="creds.json empty"
    elif ! jq empty "$creds_file" 2>/dev/null; then
        needs_restore=1
        restore_reason="creds.json invalid JSON"
    else
        # creds.json is intact — also check for the *Baileys key-file corruption signature*:
        # creds present but pre-key-*.json or app-state-sync-key-*.json files are gone or empty.
        # Snapshot must contain those keys for us to consider it a recoverable corruption.
        auth_dir=$(dirname "$creds_file")
        snap_has_prekeys=$(tar tzf "$latest" 2>/dev/null | grep -E 'pre-key-[0-9]+\.json$' | head -n 1)
        if [ -n "$snap_has_prekeys" ]; then
            live_prekey_count=$(find "$auth_dir" -maxdepth 1 -name 'pre-key-*.json' -size +0c 2>/dev/null | wc -l)
            if [ "$live_prekey_count" = "0" ]; then
                needs_restore=1
                restore_reason="pre-key-*.json missing/empty (Baileys key corruption)"
            else
                # Spot-check one pre-key file for valid JSON; if it's mangled, restore.
                sample_pk=$(find "$auth_dir" -maxdepth 1 -name 'pre-key-*.json' | head -n 1)
                if [ -n "$sample_pk" ] && ! jq empty "$sample_pk" 2>/dev/null; then
                    needs_restore=1
                    restore_reason="pre-key file corrupted (invalid JSON in $(basename "$sample_pk"))"
                fi
            fi
        fi

        # Also check app-state-sync-key files (used by Baileys for multi-device state).
        if [ "$needs_restore" = "0" ]; then
            snap_has_appstate=$(tar tzf "$latest" 2>/dev/null | grep -E 'app-state-sync-key-.+\.json$' | head -n 1)
            if [ -n "$snap_has_appstate" ]; then
                live_appstate_count=$(find "$auth_dir" -maxdepth 1 -name 'app-state-sync-key-*.json' -size +0c 2>/dev/null | wc -l)
                if [ "$live_appstate_count" = "0" ]; then
                    needs_restore=1
                    restore_reason="app-state-sync-key-*.json missing/empty"
                fi
            fi
        fi
    fi

    [ "$needs_restore" = "0" ] && continue

    # Determine restore parent: the directory that contains the auth_dir.
    if [ -n "$creds_file" ]; then
        target_parent=$(dirname "$(dirname "$creds_file")")
    else
        # No creds at all. Restore into the volume root and let bot pick it up.
        target_parent="$vol"
    fi

    auth_name=$(tar tzf "$latest" 2>/dev/null | head -n 1 | cut -d/ -f1)
    [ -z "$auth_name" ] && continue

    # Move the broken dir aside (don't lose evidence).
    if [ -d "$target_parent/$auth_name" ]; then
        mv "$target_parent/$auth_name" "$target_parent/${auth_name}.broken.$(date +%s)" 2>/dev/null
    fi

    if tar xzf "$latest" -C "$target_parent" 2>/dev/null; then
        chown -R "$WINGS_UID:$WINGS_GID" "$target_parent/$auth_name" 2>/dev/null
        restored=$((restored+1))
        echo "[$(ts)] RESTORED $uuid from $latest → $target_parent/$auth_name (reason: $restore_reason)"

        # Update wxn_bot_health (cumulative counter + last-seen timestamp) AND insert one
        # row into wxn_bot_crashes (event log) per restore. The event log is what the
        # dashboard uses for accurate 24h counts; the cumulative counter on wxn_bot_health
        # is for lifetime stats and the per-server detail view.
        if command -v mysql >/dev/null 2>&1 && [ -n "${WXN_DB_NAME:-wolfxcore}" ] && [ -n "${WXN_DB_USER:-}" ]; then
            esc_reason=$(printf '%s' "${restore_reason:-restore}" | sed "s/'/''/g" | head -c 200)
            mysql -u "${WXN_DB_USER}" -p"${WXN_DB_PASS:-}" -h "${WXN_DB_HOST:-localhost}" "${WXN_DB_NAME}" \
                -e "INSERT INTO wxn_bot_health (server_id, session_restores, last_session_restore_at, created_at, updated_at)
                    SELECT id, 1, NOW(), NOW(), NOW() FROM servers WHERE uuid='${uuid}'
                    ON DUPLICATE KEY UPDATE session_restores = session_restores + 1, last_session_restore_at = NOW(), updated_at = NOW();
                    INSERT INTO wxn_bot_crashes (server_id, event, reason, occurred_at)
                    SELECT id, 'panel:session.restored', '${esc_reason}', NOW() FROM servers WHERE uuid='${uuid}';" \
                >/dev/null 2>&1 || true
        fi

        if [ -n "$PANEL_NOTIFY_URL" ] && [ -n "$PANEL_TOKEN" ]; then
            curl -fsS -m 5 -H "Authorization: Bearer $PANEL_TOKEN" -H 'Content-Type: application/json' \
                -d "{\"server_uuid\":\"$uuid\",\"snapshot\":\"$(basename "$latest")\",\"reason\":\"$restore_reason\"}" \
                "$PANEL_NOTIFY_URL" >/dev/null 2>&1 || true
        fi
    fi
done

[ "$restored" -gt 0 ] && echo "[$(ts)] restore pass: checked=$checked restored=$restored"
