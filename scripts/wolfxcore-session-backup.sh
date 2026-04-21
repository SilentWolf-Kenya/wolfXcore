#!/bin/bash
# wolfxcore-session-backup.sh
# Snapshots Baileys auth folders for every wolfXcore volume, atomically.
# Skips snapshotting when creds.json looks corrupt — those volumes are restore candidates.
# Install: copy to /usr/local/bin/, mark +x, schedule via cron every 5 minutes.

set -u

VOLUMES_DIR="${VOLUMES_DIR:-/var/lib/wolfxcore/volumes}"
BACKUP_DIR="${BACKUP_DIR:-/var/backups/baileys-sessions}"
MAX_SNAPSHOTS="${MAX_SNAPSHOTS:-5}"
LOG="${LOG:-/var/log/wolfxcore-session-backup.log}"

mkdir -p "$BACKUP_DIR"
exec >>"$LOG" 2>&1

ts() { date '+%Y-%m-%d %H:%M:%S'; }

[ -d "$VOLUMES_DIR" ] || { echo "[$(ts)] volumes dir missing: $VOLUMES_DIR"; exit 1; }

snapped=0
skipped=0
for vol in "$VOLUMES_DIR"/*/; do
    uuid=$(basename "$vol")
    [ -d "$vol" ] || continue

    creds_file=$(find "$vol" -maxdepth 5 -name 'creds.json' -type f 2>/dev/null | head -n 1)
    [ -z "$creds_file" ] && continue

    # Validate creds.json (non-empty + parseable JSON). Bad → don't overwrite a good snapshot.
    if [ ! -s "$creds_file" ] || ! jq empty "$creds_file" 2>/dev/null; then
        skipped=$((skipped+1))
        continue
    fi

    auth_dir=$(dirname "$creds_file")
    auth_name=$(basename "$auth_dir")
    parent_dir=$(dirname "$auth_dir")

    snap_root="$BACKUP_DIR/$uuid"
    mkdir -p "$snap_root"
    stamp=$(date +%Y%m%d-%H%M%S)
    out="$snap_root/snap-$stamp.tar.gz"

    if tar czf "$out.tmp" -C "$parent_dir" "$auth_name" 2>/dev/null; then
        mv -f "$out.tmp" "$out"
        snapped=$((snapped+1))
        # Prune
        ls -t "$snap_root"/snap-*.tar.gz 2>/dev/null | tail -n +$((MAX_SNAPSHOTS+1)) | xargs -r rm -f
    else
        rm -f "$out.tmp"
    fi
done

echo "[$(ts)] backup pass complete: snapshotted=$snapped skipped=$skipped"
