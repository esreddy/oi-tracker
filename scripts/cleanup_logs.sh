#!/bin/bash

LOG_DIR="/Users/sudhakar/Herd/oi-tracker/writable/logs"
DAYS_KEEP=3

STAMP_FILE="/Users/sudhakar/Herd/oi-tracker/writable/logs/.last_log_cleanup"
TODAY=$(date "+%Y-%m-%d")

# If already cleaned today, skip
if [ -f "$STAMP_FILE" ]; then
    LAST_RUN=$(cat "$STAMP_FILE")
    if [ "$LAST_RUN" = "$TODAY" ]; then
        exit 0
    fi
fi

echo "[$(date)] Running log cleanup..."

# delete old logs
find "$LOG_DIR" -type f -name "log-*.log" -mtime +$DAYS_KEEP -print -delete

# update stamp
echo "$TODAY" > "$STAMP_FILE"

echo "[$(date)] Cleanup done."
