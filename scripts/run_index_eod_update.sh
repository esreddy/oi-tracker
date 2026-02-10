#!/bin/bash
set -u

# ============================================================
# SAFE WRAPPER: index:eod:update
# - Waits for internet after login / before running
# - Logs everything
# - Tracks consecutive failures
# - Auto-disables launchd job after repeated failures
# ============================================================

PHP="/Users/sudhakar/Library/Application Support/Herd/bin/php"
APP="/Users/sudhakar/Herd/oi-tracker"

LOG_DIR="$APP/writable/logs"
LOG="$LOG_DIR/index_eod_update.log"
OUT="$LOG_DIR/index_eod_launchd.out"
ERR="$LOG_DIR/index_eod_launchd.err"

STATE_DIR="$APP/writable/state"
FAIL_FILE="$STATE_DIR/index_eod_fail_count.txt"

JOB_PLIST="$HOME/Library/LaunchAgents/com.oi.indexeod.update.plist"
JOB_LABEL="com.oi.indexeod.update"

MAX_FAILS=5

# --- Internet wait settings ---
MAX_WAIT=300          # seconds (5 minutes)
SLEEP_INTERVAL=5      # seconds
CHECK_URL="https://1.1.1.1"   # fast connectivity check

mkdir -p "$LOG_DIR"
mkdir -p "$STATE_DIR"

ts() { date "+%Y-%m-%d %H:%M:%S"; }

notify_success() {
  local msg="$1"
  /usr/bin/osascript -e "display notification \"${msg//\"/\\\"}\" with title \"OI Tracker ✅ $(date '+%H:%M')\""
}

notify_fail() {
  local msg="$1"
  /usr/bin/osascript -e "display notification \"${msg//\"/\\\"}\" with title \"OI Tracker ❌ $(date '+%H:%M')\""
}



# Weekday-only guard (Mon=1 ... Sun=7)
dow=$(date +%u)
if [ "$dow" -ge 6 ]; then
  echo "[$(ts)] SKIP: weekend (weekday-only enabled)" >> "$LOG"
  exit 0
fi

echo "[$(ts)] ===== START index:eod:update =====" >> "$LOG"

# ============================================================
# WAIT FOR INTERNET (important for login-time runs)
# ============================================================
echo "[$(ts)] INFO: checking internet connectivity..." >> "$LOG"

elapsed=0
while ! /usr/bin/curl -s --max-time 3 "$CHECK_URL" >/dev/null 2>&1; do
  echo "[$(ts)] INFO: internet not ready, waiting... (${elapsed}s/${MAX_WAIT}s)" >> "$LOG"
  sleep "$SLEEP_INTERVAL"
  elapsed=$((elapsed + SLEEP_INTERVAL))

  if [ "$elapsed" -ge "$MAX_WAIT" ]; then
    echo "[$(ts)] SKIP: internet not available after ${MAX_WAIT}s. Exiting without failure count." >> "$LOG"
    echo "[$(ts)] ===== END (NO-INTERNET) =====" >> "$LOG"
    exit 0
  fi
done

echo "[$(ts)] INFO: internet is available. Continuing..." >> "$LOG"

# ============================================================
# Run the CI command
# ============================================================
cd "$APP" || {
  echo "[$(ts)] ERROR: cannot cd to $APP" >> "$LOG"
  exit 1
}

"$PHP" spark index:eod:update >> "$LOG" 2>&1
rc=$?

if [ $rc -eq 0 ]; then
  echo "[$(ts)] OK: command succeeded" >> "$LOG"
  echo "0" > "$FAIL_FILE"

  notify_success "Index EOD update completed successfully"

  echo "[$(ts)] ===== END (OK) =====" >> "$LOG"
  exit 0
fi


# Failure path
fail_count=0
if [ -f "$FAIL_FILE" ]; then
  fail_count=$(cat "$FAIL_FILE" 2>/dev/null || echo "0")
fi

fail_count=$((fail_count + 1))
echo "$fail_count" > "$FAIL_FILE"

echo "[$(ts)] ERROR: command failed (exit=$rc). Consecutive fails=$fail_count/$MAX_FAILS" >> "$LOG"
notify_fail "Index EOD update failed (attempt $fail_count/$MAX_FAILS)"

if [ $fail_count -ge $MAX_FAILS ]; then
  echo "[$(ts)] FAILSAFE: disabling launchd job $JOB_LABEL after $fail_count consecutive failures" >> "$LOG"
  # Disable job (user-level LaunchAgent)
  launchctl unload "$JOB_PLIST" >> "$LOG" 2>&1 || true
  echo "[$(ts)] FAILSAFE: job unloaded. Fix issue then re-enable." >> "$LOG"
fi

echo "[$(ts)] ===== END (FAIL) =====" >> "$LOG"
exit $rc
