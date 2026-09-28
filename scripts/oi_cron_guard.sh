#!/bin/bash
# oi_cron_guard.sh
# Smart cron guard for OI tracker jobs. Wrap every market job with it:
#   * * * * * LABEL="NIFTY_fetch" HEARTBEAT_SQL='...' /bin/bash .../oi_cron_guard.sh <command...>
#
# - Market window gate (Mon–Fri 09:15–15:30 IST)
# - EXEMPT_TIME: extra daily windows (pre-open / post-close), Mon–Fri or on an EXEMPT_DATES day
# - EXEMPT_SCHEDULE: date-specific timed special sessions (YYYY-MM-DD@HH:MM-HH:MM)
# - EXEMPT_DATES: full-day special sessions (run like a weekday during the normal window)
#   (all three can be set in writable/cache/special_trading_days.conf)
# - Skips NSE holidays: auto-fetched JSON cache (validated before use), FO segment by default
# - DB heartbeat (only when HEARTBEAT_SQL or HEARTBEAT_CMD is set): frozen throttle,
#   and ENRICH_REQUIRES_FRESH_FETCH=1 makes enrich wait for a fresh fetch.
#   Time-zone-proof example (mysql credentials/database from ~/.my.cnf):
#     HEARTBEAT_SQL='SELECT TIMESTAMPDIFF(SECOND,"1970-01-01",MAX(ts)) FROM oi_snapshots WHERE symbol="NIFTY";'
# - Overlap protection: per-LABEL lock, released when the job ends
# - Kill switch file, FORCE_RUN, DRY_RUN, DEBUG, compact/verbose logs
# - Logs: market_window.log (in-window decisions + runs), market_outside.log (outside-hours skips)
set -euo pipefail

# -------------------------
# Paths (edit if needed)
# -------------------------
BASE_DIR="${BASE_DIR:-/Users/sudhakar/Herd/oi-tracker}"
LOG_DIR="${LOG_DIR:-$BASE_DIR/writable/logs}"
CACHE_DIR="${CACHE_DIR:-$BASE_DIR/writable/cache}"
mkdir -p "$LOG_DIR" "$CACHE_DIR"

# -------------------------
# Config (defaults)
# -------------------------
MARKET_TZ="${MARKET_TZ:-Asia/Kolkata}"
WINDOW_START="${WINDOW_START:-0915}"
WINDOW_END="${WINDOW_END:-1530}"

# Daily extra windows (every day)
EXEMPT_TIME="${EXEMPT_TIME:-09:07-09:10,15:44-15:47}"

# Special trading config (loaded if file exists)
SPECIAL_CONF="${SPECIAL_CONF:-$CACHE_DIR/special_trading_days.conf}"
EXEMPT_DATES="${EXEMPT_DATES:-}"         # YYYY-MM-DD,YYYY-MM-DD
EXEMPT_SCHEDULE="${EXEMPT_SCHEDULE:-}"   # YYYY-MM-DD@HH:MM-HH:MM,YYYY-MM-DD@HH:MM-HH:MM

# NSE holiday cache + filters
HOLIDAY_CACHE="${HOLIDAY_CACHE:-$CACHE_DIR/nse_holidays_$(TZ="$MARKET_TZ" date +%Y).json}"
HOLIDAY_REFRESH_DAYS="${HOLIDAY_REFRESH_DAYS:-7}"
HOLIDAY_SEGMENTS="${HOLIDAY_SEGMENTS:-FO}"                # "" => all sections (except ignored)
HOLIDAY_IGNORE_SECTIONS="${HOLIDAY_IGNORE_SECTIONS:-COM}" # ignore COM by default
IGNORE_HOLIDAY="${IGNORE_HOLIDAY:-0}"
HOLIDAY_RETRY_MIN="${HOLIDAY_RETRY_MIN:-30}"              # after a failed refresh, wait before retrying NSE

# Heartbeat + behavior
FETCH_STALE_SEC="${FETCH_STALE_SEC:-120}"
FROZEN_SEC="${FROZEN_SEC:-300}"
FROZEN_THROTTLE_MIN="${FROZEN_THROTTLE_MIN:-3}"
ENRICH_REQUIRES_FRESH_FETCH="${ENRICH_REQUIRES_FRESH_FETCH:-0}"
DRY_RUN="${DRY_RUN:-0}"

# Ops controls
LABEL="${LABEL:-task}"
LOG_MODE="${LOG_MODE:-compact}" # compact|verbose
DEBUG="${DEBUG:-0}"
KILL_SWITCH_FILE="${KILL_SWITCH_FILE:-$CACHE_DIR/oi_guard.KILL}"
FORCE_RUN="${FORCE_RUN:-0}"
FORCE_RUN_NOLOCK="${FORCE_RUN_NOLOCK:-0}"

# -------------------------
# Helpers
# -------------------------
ts()        { TZ="$MARKET_TZ" date "+%Y-%m-%d %H:%M:%S"; }
ymd()       { TZ="$MARKET_TZ" date +%Y-%m-%d; }
dow()       { TZ="$MARKET_TZ" date +%u; }    # 1..7
hhmm()      { TZ="$MARKET_TZ" date +%H%M; }
minute()    { TZ="$MARKET_TZ" date +%M; }
epoch()     { TZ="$MARKET_TZ" date +%s; }
have_cmd()  { command -v "$1" >/dev/null 2>&1; }

IN_LOG="${IN_LOG:-$LOG_DIR/market_window.log}"
OUT_LOG="${OUT_LOG:-$LOG_DIR/market_outside.log}"

fmt_log() {
  local STATUS="$1" DETAILS="$2" CMD="$3"
  if [ "$LOG_MODE" = "verbose" ]; then
    echo "$(ts) ${STATUS} :: [$LABEL] ${DETAILS} :: ${CMD}"
  else
    echo "$(ts) ${STATUS} [$LABEL] ${DETAILS}"
  fi
}
log_in()  { fmt_log "$1" "$2" "$3" >> "$IN_LOG"; }
log_out() { fmt_log "$1" "$2" "$3" >> "$OUT_LOG"; }
dbg()     { [ "$DEBUG" = "1" ] && fmt_log "DEBUG" "$1" "$CMD_STR" >> "$IN_LOG" || true; }

# Load conf (conf overrides script defaults)
load_conf() {
  [ -f "$SPECIAL_CONF" ] || return 0
  # shellcheck source=/dev/null
  source "$SPECIAL_CONF" || true
}

# Lock (atomic mkdir)
acquire_lock() {
  local lockdir="/tmp/oi_cron_lock_${LABEL}"
  if mkdir "$lockdir" 2>/dev/null; then
    echo $$ > "${lockdir}/pid"
    trap "rm -rf '$lockdir' 2>/dev/null || true" EXIT
    return 0
  fi
  if [ -f "${lockdir}/pid" ]; then
    local pid; pid="$(cat "${lockdir}/pid" 2>/dev/null || true)"
    if [ -n "$pid" ] && kill -0 "$pid" 2>/dev/null; then
      return 1
    fi
    rm -rf "$lockdir" 2>/dev/null || true
    mkdir "$lockdir" 2>/dev/null || return 1
    echo $$ > "${lockdir}/pid"
    trap "rm -rf '$lockdir' 2>/dev/null || true" EXIT
    return 0
  fi
  return 1
}

# Heartbeat epoch from DB
db_last_epoch() {
  if [ -n "${HEARTBEAT_CMD:-}" ]; then
    eval "$HEARTBEAT_CMD" 2>/dev/null || echo ""
    return 0
  fi
  if [ -n "${HEARTBEAT_SQL:-}" ]; then
    have_cmd mysql || { echo ""; return 0; }
    mysql -N -B -e "$HEARTBEAT_SQL" 2>/dev/null | tr -d ' \r\n' || echo ""
    return 0
  fi
  echo ""
}

# Time window: "HH:MM-HH:MM,HH:MM-HH:MM"
in_time_ranges() {
  local ranges="$1" now; now="$(TZ="$MARKET_TZ" date +%H%M)"
  [ -n "$ranges" ] || return 1
  local IFS=',' r
  for r in $ranges; do
    r="${r// /}"
    local st="${r%-*}" en="${r#*-}"
    local stHHMM="${st/:/}" enHHMM="${en/:/}"
    if [ "$now" -ge "$stHHMM" ] && [ "$now" -lt "$enHHMM" ]; then
      return 0
    fi
  done
  return 1
}

is_weekday() {
  local d; d="$(dow)"
  [ "$d" -ge 1 ] && [ "$d" -le 5 ]
}

in_weekday_window() {
  local n; n="$(hhmm)"
  is_weekday || return 1
  [ "$n" -ge "$WINDOW_START" ] && [ "$n" -le "$WINDOW_END" ]
}

is_exempt_date_today() {
  local t; t="$(ymd)"
  [ -n "$EXEMPT_DATES" ] || return 1
  local IFS=',' d
  for d in $EXEMPT_DATES; do
    d="${d// /}"
    [ "$d" = "$t" ] && return 0
  done
  return 1
}

# If special schedule active now, echo its range and return 0
special_schedule_now() {
  local t now; t="$(ymd)"; now="$(TZ="$MARKET_TZ" date +%H%M)"
  [ -n "$EXEMPT_SCHEDULE" ] || return 1
  local IFS=',' it
  for it in $EXEMPT_SCHEDULE; do
    it="${it// /}"
    case "$it" in
      "$t@"*)
        local range="${it#*@}"
        local st="${range%-*}" en="${range#*-}"
        local stHHMM="${st/:/}" enHHMM="${en/:/}"
        if [ "$now" -ge "$stHHMM" ] && [ "$now" -lt "$enHHMM" ]; then
          echo "$range"
          return 0
        fi
      ;;
    esac
  done
  return 1
}

# NSE holiday cache freshness
cache_fresh() {
  [ -f "$HOLIDAY_CACHE" ] || return 1
  have_cmd python3 || return 0
  python3 - <<'PY' "$HOLIDAY_CACHE" "$HOLIDAY_REFRESH_DAYS"
import os,sys,time
p=sys.argv[1]; days=int(sys.argv[2])
age=time.time()-os.path.getmtime(p)
sys.exit(0 if age < days*24*3600 else 1)
PY
}

# NSE returns {"FO":[...],"CM":[...],...}; an error or HTML page must never replace a good cache
holiday_json_valid() {
  have_cmd python3 || return 0
  python3 - "$1" <<'PY'
import json,sys
try: d=json.load(open(sys.argv[1]))
except Exception: sys.exit(1)
sys.exit(0 if isinstance(d,dict) and any(isinstance(v,list) and v for v in d.values()) else 1)
PY
}

fetch_holidays() {
  local url tmp cookie stamp
  url="https://www.nseindia.com/api/holiday-master?type=trading"
  tmp="${HOLIDAY_CACHE}.tmp"
  cookie="${CACHE_DIR}/nse_cookie.txt"
  stamp="${HOLIDAY_CACHE}.lastfail"

  # After a failed refresh, keep using the old cache and don't hit NSE again for a while
  if [ -f "$stamp" ] && [ -z "$(find "$stamp" -mmin +"$HOLIDAY_RETRY_MIN" 2>/dev/null)" ]; then
    return 1
  fi

  curl -sS -c "$cookie" -b "$cookie" \
    -H "user-agent: Mozilla/5.0" -H "accept: application/json,text/plain,*/*" \
    -H "referer: https://www.nseindia.com/" \
    "https://www.nseindia.com" >/dev/null 2>&1 || true

  if curl -sS --fail -c "$cookie" -b "$cookie" \
      -H "user-agent: Mozilla/5.0" -H "accept: application/json,text/plain,*/*" \
      -H "referer: https://www.nseindia.com/" \
      "$url" > "$tmp" 2>/dev/null && holiday_json_valid "$tmp"; then
    mv "$tmp" "$HOLIDAY_CACHE"
    rm -f "$stamp"
    return 0
  fi

  rm -f "$tmp" 2>/dev/null || true
  touch "$stamp"
  return 1
}

is_holiday_today() {
  local today; today="$(TZ="$MARKET_TZ" date "+%d-%b-%Y")"
  cache_fresh || fetch_holidays || true
  [ -f "$HOLIDAY_CACHE" ] || return 1
  have_cmd python3 || return 1

  python3 - <<'PY' "$HOLIDAY_CACHE" "$today" "$HOLIDAY_SEGMENTS" "$HOLIDAY_IGNORE_SECTIONS"
import json,sys
path,today,seg,ign=sys.argv[1],sys.argv[2],(sys.argv[3] or "").strip(),(sys.argv[4] or "").strip()
wanted=set(s.strip() for s in seg.split(",") if s.strip())
ignored=set(s.strip() for s in ign.split(",") if s.strip())
try: data=json.load(open(path))
except: sys.exit(1)
if not isinstance(data,dict): sys.exit(1)
for section,arr in data.items():
  if section in ignored: continue
  if wanted and section not in wanted: continue
  if not isinstance(arr,list): continue
  for row in arr:
    d=(row.get("tradingDate") or row.get("date") or "").strip()
    if d==today:
      desc=(row.get("description") or row.get("holiday") or "").strip()
      print(f"{today}|{section}|{desc}")
      sys.exit(0)
sys.exit(1)
PY
}

# -------------------------
# Main
# -------------------------
CMD_STR="$*"
load_conf

# Kill switch
if [ -f "$KILL_SWITCH_FILE" ]; then
  log_in "SKIPPED(disabled)" "kill_switch" "$CMD_STR"
  exit 0
fi

# Force run
if [ "$FORCE_RUN" = "1" ]; then
  log_in "RUN(force)" "FORCE_RUN=1" "$CMD_STR"
else
  # Decide allow + reason (single deterministic decision)
  REASON=""
  SPEC_RANGE=""
  if SPEC_RANGE="$(special_schedule_now 2>/dev/null)"; then
    REASON="exempt_schedule"
  elif is_exempt_date_today && in_time_ranges "09:15-15:30"; then
    REASON="exempt_date"
  elif in_weekday_window; then
    REASON="normal_window"
  elif in_time_ranges "$EXEMPT_TIME" && { is_weekday || is_exempt_date_today; }; then
    REASON="exempt_time"   # pre-open / post-close windows: trading weekdays (or exempt dates) only
  else
    log_out "SKIPPED(outside_window)" "outside_hours" "$CMD_STR"
    exit 0
  fi

  # Holiday check (bypass on specials)
  if [ "$IGNORE_HOLIDAY" != "1" ] && [ "$REASON" != "exempt_schedule" ] && [ "$REASON" != "exempt_date" ]; then
    HOL_MATCH="$(is_holiday_today 2>/dev/null || true)"
    if [ -n "$HOL_MATCH" ]; then
      log_in "SKIPPED(holiday)" "holiday ${HOL_MATCH}" "$CMD_STR"
      exit 0
    fi
  fi
fi

# Lock (unless FORCE_RUN_NOLOCK)
if ! { [ "$FORCE_RUN" = "1" ] && [ "$FORCE_RUN_NOLOCK" = "1" ]; }; then
  if ! acquire_lock; then
    log_in "SKIPPED(overlap_lock)" "lock_held" "$CMD_STR"
    exit 0
  fi
fi

# Heartbeat age
LAST_EPOCH="$(db_last_epoch)"
NOW_EPOCH="$(epoch)"
AGE_SEC=""
if [ -n "$LAST_EPOCH" ] && [[ "$LAST_EPOCH" =~ ^[0-9]+$ ]]; then
  AGE_SEC=$(( NOW_EPOCH - LAST_EPOCH ))
fi
dbg "AGE_SEC=${AGE_SEC:-na} LAST_EPOCH='${LAST_EPOCH}'"

# Frozen throttle
if [ -n "${AGE_SEC:-}" ] && [ "$AGE_SEC" -ge "$FROZEN_SEC" ]; then
  m=$((10#$(minute)))
  if [ $((m % FROZEN_THROTTLE_MIN)) -ne 0 ]; then
    log_in "SKIPPED(frozen_throttle)" "age=${AGE_SEC}s throttle=${FROZEN_THROTTLE_MIN}m" "$CMD_STR"
    exit 0
  fi
fi

# Enrich requires fresh fetch
if [ "$ENRICH_REQUIRES_FRESH_FETCH" = "1" ]; then
  if [ -n "${AGE_SEC:-}" ] && [ "$AGE_SEC" -ge "$FETCH_STALE_SEC" ]; then
    log_in "SKIPPED(enrich_fetch_delayed)" "age=${AGE_SEC:-na}s stale>=${FETCH_STALE_SEC}s" "$CMD_STR"
    exit 0
  fi
fi

# Dry run
if [ "$DRY_RUN" = "1" ]; then
  log_in "DRYRUN(would_run)" "age=${AGE_SEC:-na}s" "$CMD_STR"
  exit 0
fi

# Run as a child (not exec) so the EXIT trap releases the lock when the job ends
log_in "RUN" "age=${AGE_SEC:-na}s" "$CMD_STR"
set +e
"$@"
rc=$?
set -e
exit "$rc"
