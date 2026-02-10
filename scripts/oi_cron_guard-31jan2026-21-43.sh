#!/bin/bash
# oi_cron_guard.sh
# Smart cron guard for OI tracker:
# - Market window gate (Mon–Fri 09:15–15:30)
# - Exemption windows (extra allowed time ranges)
# - Skip NSE holidays (auto-fetch + cache)
#     * Configurable segment filter via HOLIDAY_SEGMENTS:
#         - If HOLIDAY_SEGMENTS is set (e.g. "FO,CM"): only those sections count
#         - If HOLIDAY_SEGMENTS is empty/unset: ALL sections count
#     * You can ignore specific sections via HOLIDAY_IGNORE_SECTIONS (default: "COM")
# - DB heartbeat (mysql) to detect LIVE/FROZEN + delay
# - Pause enrich if fetch delayed
# - Throttle when frozen
# - DRY_RUN mode
# - Separate logs for outside-hours + auto-disable outside log after first RUN of the day
# - Verbose/compact logging
# - Overlap protection via lock
# - DEBUG mode (writes DEBUG lines into market_window.log)

set -euo pipefail

# =========================
# Defaults (override via env)
# =========================
WINDOW_START="${WINDOW_START:-0915}"
WINDOW_END="${WINDOW_END:-1530}"

# Exemption windows (allowed even outside market hours)
# Format: HH:MM-HH:MM[,HH:MM-HH:MM]
EXEMPT_TIME="${EXEMPT_TIME:-09:07-09:10,15:44-15:47}"

# Heartbeat thresholds (seconds)
FETCH_STALE_SEC="${FETCH_STALE_SEC:-120}"        # if last DB update older than this => fetch delayed
FROZEN_SEC="${FROZEN_SEC:-300}"                  # if older than this => frozen
FROZEN_THROTTLE_MIN="${FROZEN_THROTTLE_MIN:-3}"  # when frozen, run only every N minutes

# Holidays cache refresh
HOLIDAY_REFRESH_DAYS="${HOLIDAY_REFRESH_DAYS:-7}"

# Holiday behavior:
# - Set HOLIDAY_SEGMENTS="FO" or "FO,CM" to restrict
# - If HOLIDAY_SEGMENTS is empty/unset => consider ALL sections (except ignored)
HOLIDAY_SEGMENTS="${HOLIDAY_SEGMENTS:-FO}"            # e.g. "FO" or "FO,CM" or "" (all)
HOLIDAY_IGNORE_SECTIONS="${HOLIDAY_IGNORE_SECTIONS:-COM}"  # e.g. "COM,MF"

IGNORE_HOLIDAY="${IGNORE_HOLIDAY:-0}"                 # 1 => do not skip on holiday (testing)

# Behavior toggles
DRY_RUN="${DRY_RUN:-0}"                          # 1 => log only, don't execute
ENRICH_REQUIRES_FRESH_FETCH="${ENRICH_REQUIRES_FRESH_FETCH:-0}"  # 1 => block if fetch delayed
OUTSIDE_LOG="${OUTSIDE_LOG:-1}"                  # 1 => write outside-hours log until first RUN of day
LOG_MODE="${LOG_MODE:-compact}"                  # compact | verbose
LABEL="${LABEL:-task}"                           # used for lock + readability
DEBUG="${DEBUG:-0}"                              # 1 => emit DEBUG lines into market_window.log

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

dbg() {
  [ "$DEBUG" = "1" ] || return 0
  fmt_log "DEBUG" "$1" "$CMD_STR" >> "$IN_LOG"
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
# =========================
in_exempt_window() {
  [ -z "${EXEMPT_TIME:-}" ] && return 1

  local cur h1 m1 h2 m2 start end
  cur=$(date +%H%M)

  IFS=',' read -ra ranges <<< "$EXEMPT_TIME"
  for r in "${ranges[@]}"; do
    r="$(echo "$r" | tr -d ' ')"  # allow spaces after commas

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

# Prints one matching line "date|section|desc" if holiday.
# Returns 0 if holiday, 1 otherwise.
is_holiday_today() {
  local today
  today=$(date "+%d-%b-%Y")

  if ! cache_fresh; then
    fetch_holidays || true
  fi

  [ -f "$HOLIDAY_CACHE" ] || return 1
  have_cmd python3 || return 1

  python3 - <<'PY' "$HOLIDAY_CACHE" "$today" "$HOLIDAY_SEGMENTS" "$HOLIDAY_IGNORE_SECTIONS"
import json,sys

path=sys.argv[1]
today=sys.argv[2]
seg_filter=(sys.argv[3] or "").strip()
ignore=(sys.argv[4] or "").strip()

wanted=set(s.strip() for s in seg_filter.split(",") if s.strip())
ignored=set(s.strip() for s in ignore.split(",") if s.strip())

try:
  data=json.load(open(path))
except Exception:
  sys.exit(1)

if not isinstance(data,dict):
  sys.exit(1)

for section, arr in data.items():
  if section in ignored:
    continue
  if wanted and section not in wanted:
    continue
  if not isinstance(arr,list):
    continue
  for row in arr:
    d=(row.get("tradingDate") or row.get("date") or "").strip()
    if d==today:
      desc=(row.get("description") or row.get("holiday") or "").strip()
      print(f"{today}|{section}|{desc}")
      sys.exit(0)

sys.exit(1)
PY
}

# =========================
# DB Heartbeat
# =========================
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
    mysql -N -B -e "$HEARTBEAT_SQL" 2>/dev/null | tr -d ' \r\n' || echo ""
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
    trap "rm -rf '$lockdir' 2>/dev/null || true" EXIT
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
      trap "rm -rf '$lockdir' 2>/dev/null || true" EXIT
      return 0
    fi
  fi

  return 1
}


# =========================
# Main
# =========================
CMD_STR="$*"
dbg "NOW=$(ts) DOW=$(dow) HHMM=$(hhmm) window=${WINDOW_START}-${WINDOW_END} exempt='${EXEMPT_TIME}' holiday_segments='${HOLIDAY_SEGMENTS}' holiday_ignore='${HOLIDAY_IGNORE_SECTIONS}' ignore_holiday=${IGNORE_HOLIDAY}"

# Outside-hours handling (market window OR exempt window)
if ! in_window; then
  if in_exempt_window; then
    log_in "RUN(exempt_window)" "exempt_time=${EXEMPT_TIME}" "$CMD_STR"
  else
    log_out "SKIPPED(outside_window)" "outside_hours" "$CMD_STR"
    exit 0
  fi
fi

# NSE holiday skip (with segment filter + ignored sections)
if [ "$IGNORE_HOLIDAY" != "1" ]; then
  HOL_MATCH="$(is_holiday_today 2>/dev/null || true)"
  if [ -n "$HOL_MATCH" ]; then
    dbg "Holiday match: ${HOL_MATCH} cache=${HOLIDAY_CACHE}"
    log_in "SKIPPED(holiday)" "holiday ${HOL_MATCH}" "$CMD_STR"
    exit 0
  fi
else
  dbg "IGNORE_HOLIDAY=1 (holiday check bypassed)"
fi

# Prevent overlapping runs
if ! acquire_lock; then
  log_in "SKIPPED(overlap_lock)" "lock_held" "$CMD_STR"
  exit 0
fi

# Heartbeat age
LAST_EPOCH="$(db_last_epoch)"
NOW_EPOCH="$(epoch)"
dbg "Heartbeat raw LAST_EPOCH='${LAST_EPOCH}' HEARTBEAT_SQL='${HEARTBEAT_SQL:-}'"

AGE_SEC=""
if [ "$LAST_EPOCH" != "" ] 2>/dev/null; then
  if [[ "$LAST_EPOCH" =~ ^[0-9]+$ ]]; then
    AGE_SEC=$(( NOW_EPOCH - LAST_EPOCH ))
  fi
fi
dbg "Computed AGE_SEC='${AGE_SEC:-}' NOW_EPOCH=${NOW_EPOCH}"

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
  if [ "$AGE_SEC" != "" ] && [ "$AGE_SEC" -ge "$FETCH_STALE_SEC" ]; then
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
