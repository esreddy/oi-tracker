#!/bin/bash
# Daily log housekeeping for writable/logs (cron: once a day, after market close).
#
# 1) Deletes CodeIgniter's own log-YYYY-MM-DD.log files older than DAYS_KEEP days.
# 2) Rotates every other *.log (cron job logs, guard logs) once it is larger than
#    ROTATE_SIZE_KB: the log is moved to <name>.log.<timestamp> and an empty file with
#    the old modification time is left in its place, so the dashboard's
#    "Fetch cron / Enrich cron" freshness keeps showing the last real write.
#    Rotated copies are gzipped on the next run (a job may still be appending right
#    after a rotation) and only the newest ROTATE_KEEP copies per log are kept.
#
# A stamp file limits it to one run per day. Paths and limits can be overridden via env.

set -u

LOG_DIR="${LOG_DIR:-/Users/sudhakar/Herd/oi-tracker/writable/logs}"
DAYS_KEEP="${DAYS_KEEP:-3}"
ROTATE_SIZE_KB="${ROTATE_SIZE_KB:-5120}"   # rotate a log once it is larger than 5 MB
ROTATE_KEEP="${ROTATE_KEEP:-8}"            # rotated copies kept per log

STAMP_FILE="$LOG_DIR/.last_log_cleanup"
TODAY=$(date "+%Y-%m-%d")

# If already cleaned today, skip
if [ -f "$STAMP_FILE" ] && [ "$(cat "$STAMP_FILE")" = "$TODAY" ]; then
    exit 0
fi

echo "[$(date)] Running log cleanup..."

# 1) CodeIgniter logs
find "$LOG_DIR" -type f -name "log-*.log" -mtime +"$DAYS_KEEP" -print -delete

# 2a) Compress copies rotated on earlier runs
find "$LOG_DIR" -maxdepth 1 -type f -name "*.log.[0-9]*" ! -name "*.gz" -mmin +10 -print -exec gzip -f {} \;

# 2b) Rotate large logs, keeping an empty file with the old mtime
STAMP=$(date "+%Y%m%d-%H%M%S")
find "$LOG_DIR" -maxdepth 1 -type f -name "*.log" ! -name "log-*.log" -size +"${ROTATE_SIZE_KB}"k | while read -r f; do
    mv "$f" "$f.$STAMP" && touch -r "$f.$STAMP" "$f" && echo "rotated: $f -> $f.$STAMP"
done

# 2c) Keep only the newest ROTATE_KEEP rotated copies per log
for f in "$LOG_DIR"/*.log; do
    [ -e "$f" ] || continue
    ls -1t "$f".[0-9]* 2>/dev/null | tail -n +"$((ROTATE_KEEP + 1))" | while read -r old; do
        rm -f "$old" && echo "removed: $old"
    done
done

# update stamp
echo "$TODAY" > "$STAMP_FILE"

echo "[$(date)] Cleanup done."
