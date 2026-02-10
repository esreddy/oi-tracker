#!/bin/bash
set -euo pipefail

# ==================================================
# OI-TRACKER BACKUP (macOS)
# --------------------------------------------------
# • Daily backup → keep last 7 days
# • Monthly backup → keep previous month only (-1)
# • Run once per day (skips if today's backup exists)
# • Notify on failure (macOS notification)
# ==================================================

SOURCE_DIR="/Users/sudhakar/Herd/oi-tracker"
BACKUP_ROOT="/Users/sudhakar/Google Drive/Backups/oi-tracker"
DAILY_DIR="$BACKUP_ROOT/daily"
MONTHLY_DIR="$BACKUP_ROOT/monthly"
LOG_FILE="$BACKUP_ROOT/backup.log"

DATE_TODAY="$(date +%Y-%m-%d)"
MONTH_PREV="$(date -v-1m +%Y-%m)"   # macOS date supports -v

DAILY_FILE="$DAILY_DIR/$DATE_TODAY.tar.gz"

mkdir -p "$DAILY_DIR" "$MONTHLY_DIR"

notify_fail() {
  local msg="$1"
  local tailtxt=""

  if [ -f "$LOG_FILE" ]; then
    tailtxt="$(tail -n 8 "$LOG_FILE" | tr '\n' ' ' | sed 's/  */ /g')"
  fi

  echo "[$(date '+%Y-%m-%d %H:%M:%S')] ERROR: $msg" >> "$LOG_FILE"

  /usr/bin/osascript -e "display notification \"${msg//\"/\\\"} | Last: ${tailtxt//\"/\\\"}\" with title \"OI Backup ❌ $(date '+%H:%M')\""
}


# If anything fails, notify
trap 'notify_fail "Backup failed. Check: $LOG_FILE"' ERR

echo "[$(date '+%Y-%m-%d %H:%M:%S')] START backup" >> "$LOG_FILE"

# Sanity check
if [ ! -d "$SOURCE_DIR" ]; then
  notify_fail "Source folder not found: $SOURCE_DIR"
  exit 1
fi

# --------------------------------------------------
# DAILY: Run only once per day
# Skip if today's backup already exists and is non-empty
# --------------------------------------------------
if [ -s "$DAILY_FILE" ]; then
  echo "[$(date '+%Y-%m-%d %H:%M:%S')] SKIP: Daily backup already exists: $DAILY_FILE" >> "$LOG_FILE"
  exit 0
fi

# Create daily backup
tar -czf "$DAILY_FILE" \
  -C "$(dirname "$SOURCE_DIR")" "$(basename "$SOURCE_DIR")" \
  >> "$LOG_FILE" 2>&1

echo "[$(date '+%Y-%m-%d %H:%M:%S')] DAILY OK: $DAILY_FILE" >> "$LOG_FILE"

# Keep only last 7 daily backups
find "$DAILY_DIR" -type f -name "*.tar.gz" -mtime +7 -print -delete >> "$LOG_FILE" 2>&1

# --------------------------------------------------
# MONTHLY: Only on the 1st day of month
# Keep only previous month (-1)
# --------------------------------------------------
if [ "$(date +%d)" = "01" ]; then
  MONTHLY_FILE="$MONTHLY_DIR/$MONTH_PREV.tar.gz"

  # Create monthly backup (overwrite if exists)
  tar -czf "$MONTHLY_FILE" \
    -C "$(dirname "$SOURCE_DIR")" "$(basename "$SOURCE_DIR")" \
    >> "$LOG_FILE" 2>&1

  echo "[$(date '+%Y-%m-%d %H:%M:%S')] MONTHLY OK: $MONTHLY_FILE" >> "$LOG_FILE"

  # Keep only previous month backup file
  find "$MONTHLY_DIR" -type f -name "*.tar.gz" \
    ! -name "$(basename "$MONTHLY_FILE")" -print -delete >> "$LOG_FILE" 2>&1
fi

echo "[$(date '+%Y-%m-%d %H:%M:%S')] END backup" >> "$LOG_FILE"
