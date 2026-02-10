#!/bin/bash
# oi_cron_guard.sh
# Smart cron guard for OI tracker:
# - Market window gate (Mon–Fri 09:15–15:30)
# - Skip NSE holidays (auto-fetch + cache)
# - DB heartbeat (mysql) to detect LIVE/FROZEN + delay
# - Pause enrich if fetch delayed
# - Throttle when frozen
# - DRY_RUN mode
# - Separate logs for outside-hours + auto-disable outside log after first RUN of the day
# - Verbose/compact logging
# - Overlap protection via lock

set -u

# =========================
# Defaults (override via env)
# =========================
WINDOW_START="${WINDOW_START:-0915}"
WINDOW_END="${WINDOW_END:-1530}"

# Exemption windows (allowed even outside market hours)
# Format: HH:MM-HH:MM[,HH:MM-HH:MM]
EXEMPT_TIME="${EXEMPT_TIME:-09:08-09:10,15:45-15:46}"
#45 15 * * 1-5 LABEL="EOD_NIFTY" EXEMPT_TIME="15:45-15:46" /bin/bash /Users/sudhakar/Herd/oi-tracker/scripts/oi_cron_guard.sh /usr/local/bin/gtimeout 90 "/Users/sudhakar/Library/Application Support/Herd/bin/php" /Users/sudhakar/Herd/oi-tracker/spark oi:enrich NIFTY 10 >> /Users/sudhakar/Herd/oi-tracker/writable/logs/oi_eod.log 2>&1


# Heartbeat thresholds (seconds)
FETCH_STALE_SEC="${FETCH_STALE_SEC:-120}"        # if last DB update older than this => fetch delayed
FROZEN_SEC="${FROZEN_SEC:-300}"                  # if older than this => frozen
FROZEN_THROTTLE_MIN="${FROZEN_THROTTLE_MIN:-3}"  # when frozen, run only every N minutes

# Holidays cache refresh
HOLIDAY_REFRESH_DAYS="${HOLIDAY_REFRESH_DAYS:-7}"

# Behavior toggles
DRY_RUN="${DRY_RUN:-0}"                          # 1 => log only, don't execute
ENRICH_REQUIRES_FRESH_FETCH="${ENRICH_REQUIRES_FRESH_FETCH:-0}"  # 1 => block if fetch delayed
OUTSIDE_LOG="${OUTSIDE_LOG:-1}"                  # 1 => write outside-hours log until first RUN of day
LOG_MODE="${LOG_MODE:-compact}"                  # compact | verbose
LABEL="${LABEL:-task}"                           # used for lock + readability

# Paths
BASE_DIR="${BASE_DIR:-/Users/sudhakar/Herd/oi-tracker}"
LOG_DIR="${LOG_DIR:-$BASE_DIR/writable/logs}"
CACHE_DIR="${CACHE_DIR:-$BASE_DIR/writable/cache}"

IN_LOG="${IN_LOG:-$LOG_DIR/market_window.log}"        # in-market decisions + runs
OUT_LOG="${OUT_LOG:-$LOG_DIR/market_outside.log}"     # outside-hours skips only

# NSE holiday cache file (per year)
HOLIDAY_CACHE="${HOLIDAY_CACHE:-$CACHE_DIR/nse_holidays_$(date +%Y).json}"

# First-run flag (per day). After first RUN, outside-hours log auto-disables for rest of day.
FIRST_RUN_FLAG="${FIRST_RUN_FLAG:-$CACHE_DIR/first_market_run_$(date +%Y-%m-%d).flag}"

# =========================
# Helpers
# =========================
mkdir -p "$LOG_DIR" "$CACHE_DIR"

ts() { date "+%Y-%m-%d %H:%M:%S"; }
dow() { date +%u; }          # 1=Mon ... 7=Sun
hhmm() { date +%H%M; }       # HHMM
minute() { date +%M; }       # 00..59
epoch() { date +%s; }

have_cmd() { command -v "$1" >/dev/null 2>&1; }

DEBUG="${DEBUG:-0}"

dbg() {
  [ "$DEBUG" = "1" ] || return 0
  # write debug into market_window.log so you can tail only one place
  fmt_log "DEBUG" "$1" "$CMD_STR" >> "$IN_LOG"
}

fmt_log() {
  # $1 = STATUS, $2 = DETAILS, $3 = COMMAND
  local STATUS="$1"
  local DETAILS="$2"
  local CMD="$3"
  if [ "$LOG_MODE" = "verbose" ]; then
    echo "$(ts) ${STATUS} :: [$LABEL] ${DETAILS} :: ${CMD}"
  else
    echo "$(ts) ${STATUS} [$LABEL] ${DETAILS}"
  fi
}

log_in()  { fmt_log "$1" "$2" "$3" >> "$IN_LOG"; }
log_out() {
  # Only until first RUN of day
  [ "$OUTSIDE_LOG" = "1" ] || return 0
  [ -f "$FIRST_RUN_FLAG" ] && return 0
  fmt_log "$1" "$2" "$3" >> "$OUT_LOG"
}

# =========================
# Market window gate
# =========================
in_window() {
  local DOW NOW
  DOW=$(dow)
  NOW=$(hhmm)

  # Mon-Fri only
  if [ "$DOW" -lt 1 ] || [ "$DOW" -gt 5 ]; then
    return 1
  fi

  # 09:15..15:30
  if [ "$NOW" -lt "$WINDOW_START" ] || [ "$NOW" -gt "$WINDOW_END" ]; then
    return 1
  fi

  return 0
}
# =========================
# Exemption time window
# Format:
#   EXEMPT_TIME="HH:MM-HH:MM[,HH:MM-HH:MM]"
# Example:
#   "15:45-15:46"
#   "15:40-16:00"
#   "09:00-09:10,15:45-15:46"
# =========================
in_exempt_window() {
  [ -z "${EXEMPT_TIME:-}" ] && return 1

  local now cur h1 m1 h2 m2 start end
  cur=$(date +%H%M)

  IFS=',' read -ra ranges <<< "$EXEMPT_TIME"
  for r in "${ranges[@]}"; do
    start="${r%-*}"
    end="${r#*-}"

    h1="${start%:*}"; m1="${start#*:}"
    h2="${end%:*}";   m2="${end#*:}"

    start="${h1}${m1}"
    end="${h2}${m2}"

    if [ "$cur" -ge "$start" ] && [ "$cur" -lt "$end" ]; then
      return 0
    fi
  done

  return 1
}

# =========================
# NSE Holiday fetch + cache
# =========================
cache_fresh() {
  # 0 if fresh, 1 if stale/missing
  if [ ! -f "$HOLIDAY_CACHE" ]; then return 1; fi
  if ! have_cmd python3; then
    # If python missing, consider cache fresh if file exists (avoid blocking)
    return 0
  fi
  python3 - <<'PY' "$HOLIDAY_CACHE" "$HOLIDAY_REFRESH_DAYS"
import os,sys,time
p=sys.argv[1]; days=int(sys.argv[2])
age=time.time()-os.path.getmtime(p)
sys.exit(0 if age < days*24*3600 else 1)
PY
}

fetch_holidays() {
  # NSE sometimes requires cookie handshake + headers
  local url tmp cookie
  url="https://www.nseindia.com/api/holiday-master?type=trading"
  tmp="${HOLIDAY_CACHE}.tmp"
  cookie="${CACHE_DIR}/nse_cookie.txt"

  # initial handshake
  curl -sS -c "$cookie" -b "$cookie" \
    -H "user-agent: Mozilla/5.0" \
    -H "accept: application/json,text/plain,*/*" \
    -H "referer: https://www.nseindia.com/" \
    "https://www.nseindia.com" >/dev/null 2>&1 || true

  # fetch holiday json
  curl -sS --fail -c "$cookie" -b "$cookie" \
    -H "user-agent: Mozilla/5.0" \
    -H "accept: application/json,text/plain,*/*" \
    -H "referer: https://www.nseindia.com/" \
    "$url" > "$tmp" 2>/dev/null && mv "$tmp" "$HOLIDAY_CACHE" || rm -f "$tmp" 2>/dev/null || true
}

is_holiday_today() {
  # returns 0 if holiday, 1 otherwise (including "unknown")
  local today
  today=$(date "+%d-%b-%Y")  # typical NSE date format

  # ensure cache
  if ! cache_fresh; then
    fetch_holidays || true
  fi

  # if cache missing or python missing, do not block market jobs
  [ -f "$HOLIDAY_CACHE" ] || return 1
  have_cmd python3 || return 1

  python3 - <<'PY' "$HOLIDAY_CACHE" "$today"
import json,sys
path=sys.argv[1]; today=sys.argv[2]
try:
  data=json.load(open(path))
except Exception:
  sys.exit(1)

items=[]
if isinstance(data,dict):
  for k,v in data.items():
    if isinstance(v,list):
      items += v

for row in items:
  d=(row.get("tradingDate") or row.get("date") or "").strip()
  if d==today:
    sys.exit(0)
sys.exit(1)
PY
}

# =========================
# DB Heartbeat
# =========================
# Provide one of:
#   HEARTBEAT_CMD='echo 1735...'
#   HEARTBEAT_SQL='SELECT UNIX_TIMESTAMP(MAX(created_at)) FROM oi_snapshots WHERE symbol="NIFTY";'
db_last_epoch() {
  if [ "${HEARTBEAT_CMD:-}" != "" ]; then
    eval "$HEARTBEAT_CMD" 2>/dev/null || echo ""
    return 0
  fi

  if [ "${HEARTBEAT_SQL:-}" != "" ]; then
    if ! have_cmd mysql; then
      echo ""
      return 0
    fi
    mysql -N -B -e "$HEARTBEAT_SQL" 2>/dev/null || echo ""
    return 0
  fi

  echo ""
}

# =========================
# Overlap lock (atomic mkdir)
# =========================
acquire_lock() {
  local lockdir="/tmp/oi_cron_lock_${LABEL}"

  if mkdir "$lockdir" 2>/dev/null; then
    echo $$ > "${lockdir}/pid"
    trap 'rm -rf "$lockdir" 2>/dev/null || true' EXIT
    return 0
  fi

  if [ -f "${lockdir}/pid" ]; then
    local pid
    pid=$(cat "${lockdir}/pid" 2>/dev/null || echo "")
    if [ "$pid" != "" ] && kill -0 "$pid" 2>/dev/null; then
      return 1
    fi
    rm -rf "$lockdir" 2>/dev/null || true

    if mkdir "$lockdir" 2>/dev/null; then
      echo $$ > "${lockdir}/pid"
      trap 'rm -rf "$lockdir" 2>/dev/null || true' EXIT
      return 0
    fi
  fi

  return 1
}



# =========================
# Main
# =========================
CMD_STR="$*"

# Outside-hours handling
if ! in_window; then
  if in_exempt_window; then
    log_in "RUN(exempt_window)" "exempt_time=${EXEMPT_TIME}" "$CMD_STR"
  else
    log_out "SKIPPED(outside_window)" "outside_hours" "$CMD_STR"
    exit 0
  fi
fi


# NSE holiday skip
if is_holiday_today; then
  log_in "SKIPPED(holiday)" "holiday" "$CMD_STR"
  exit 0
fi

# Prevent overlapping runs
if ! acquire_lock; then
  log_in "SKIPPED(overlap_lock)" "lock_held" "$CMD_STR"
  exit 0
fi

# Heartbeat age
LAST_EPOCH="$(db_last_epoch)"
NOW_EPOCH="$(epoch)"

AGE_SEC=""
if [ "$LAST_EPOCH" != "" ] 2>/dev/null; then
  # ensure numeric
  if [[ "$LAST_EPOCH" =~ ^[0-9]+$ ]]; then
    AGE_SEC=$(( NOW_EPOCH - LAST_EPOCH ))
  fi
fi

# Throttle when frozen
if [ "$AGE_SEC" != "" ] && [ "$AGE_SEC" -ge "$FROZEN_SEC" ]; then
  m=$((10#$(minute)))
  if [ $((m % FROZEN_THROTTLE_MIN)) -ne 0 ]; then
    log_in "SKIPPED(frozen_throttle)" "age=${AGE_SEC}s throttle=${FROZEN_THROTTLE_MIN}m" "$CMD_STR"
    exit 0
  fi
fi

# Pause enrich if fetch delayed
if [ "$ENRICH_REQUIRES_FRESH_FETCH" = "1" ]; then
  if [ "$AGE_SEC" = "" ] || [ "$AGE_SEC" -ge "$FETCH_STALE_SEC" ]; then
    log_in "SKIPPED(enrich_fetch_delayed)" "age=${AGE_SEC:-na}s stale>=${FETCH_STALE_SEC}s" "$CMD_STR"
    exit 0
  fi
fi

# Dry run
if [ "$DRY_RUN" = "1" ]; then
  log_in "DRYRUN(would_run)" "age=${AGE_SEC:-na}s" "$CMD_STR"
  exit 0
fi

# Run
log_in "RUN" "age=${AGE_SEC:-na}s" "$CMD_STR"
touch "$FIRST_RUN_FLAG" 2>/dev/null || true

exec "$@"
