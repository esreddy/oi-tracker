#!/bin/bash
# oi_cron_guard.sh
# Smart cron guard for OI tracker:
# - Market window gate (Mon–Fri 09:15–15:30)
# - Exemption windows (extra allowed time ranges)
# - ✅ Date-specific special sessions via EXEMPT_SCHEDULE (Option A)
# - ✅ Full-day specials via EXEMPT_DATES (runs like weekday during normal window)
# - Skip NSE holidays (auto-fetch + cache) with segment filter + ignore list (default ignore COM)
# - DB heartbeat (mysql) to detect LIVE/FROZEN + delay
# - Pause enrich if fetch delayed
# - Throttle when frozen
# - DRY_RUN mode
# - Separate logs for outside-hours + auto-disable outside log after first RUN of the day
# - Verbose/compact logging
# - Overlap protection via lock
# - DEBUG mode (writes DEBUG lines into market_window.log)
# - ✅ Auto-expire old special dates from special_trading_days.conf (once per day)

# oi_cron_guard_v4.sh (clean & short)
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
HOLIDAY_SEGMENTS="${HOLIDAY_SEGMENTS:-}"                  # "" => all sections
HOLIDAY_IGNORE_SECTIONS="${HOLIDAY_IGNORE_SECTIONS:-COM}" # ignore COM by default
IGNORE_HOLIDAY="${IGNORE_HOLIDAY:-0}"

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

# State for dashboard
MARKET_STATE_JSON="${MARKET_STATE_JSON:-$CACHE_DIR/market_state.json}"

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

in_weekday_window() {
  local d n; d="$(dow)"; n="$(hhmm)"
  [ "$d" -ge 1 ] && [ "$d" -le 5 ] || return 1
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

fetch_holidays() {
  local url tmp cookie
  url="https://www.nseindia.com/api/holiday-master?type=trading"
  tmp="${HOLIDAY_CACHE}.tmp"
  cookie="${CACHE_DIR}/nse_cookie.txt"

  curl -sS -c "$cookie" -b "$cookie" \
    -H "user-agent: Mozilla/5.0" -H "accept: application/json,text/plain,*/*" \
    -H "referer: https://www.nseindia.com/" \
    "https://www.nseindia.com" >/dev/null 2>&1 || true

  curl -sS --fail -c "$cookie" -b "$cookie" \
    -H "user-agent: Mozilla/5.0" -H "accept: application/json,text/plain,*/*" \
    -H "referer: https://www.nseindia.com/" \
    "$url" > "$tmp" 2>/dev/null && mv "$tmp" "$HOLIDAY_CACHE" || rm -f "$tmp" 2>/dev/null || true
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

# market_state.json (simple)
write_market_state() {
  local state="$1" reason="$2" extra="$3"
  if have_cmd python3; then
    python3 - <<'PY' "$MARKET_STATE_JSON" "$(ts)" "$state" "$reason" "$extra" "$SPECIAL_CONF"
import json,sys,os,re,datetime
path,ts,state,reason,extra,conf=sys.argv[1:7]
obj={"ts":ts,"state":state,"reason":reason}
if extra: obj["extra"]=extra

# next special (best effort)
try:
  if os.path.exists(conf):
    txt=open(conf,'r',encoding='utf-8',errors='ignore').read()
    def getv(k):
      m=re.search(r'^%s\s*=\s*"([^"]*)"'%re.escape(k), txt, re.M)
      return (m.group(1) if m else "").strip()
    dates=[d.strip().replace(' ','') for d in getv("EXEMPT_DATES").split(",") if d.strip()]
    sched=[s.strip().replace(' ','') for s in getv("EXEMPT_SCHEDULE").split(",") if s.strip()]
    today=datetime.date.today()
    cand=[]
    for d in dates:
      try:
        dt=datetime.date.fromisoformat(d)
        if dt>=today: cand.append((dt, "09:15-15:30"))
      except: pass
    for it in sched:
      if '@' not in it: continue
      d,r=it.split('@',1)
      try:
        dt=datetime.date.fromisoformat(d)
        if dt>=today: cand.append((dt, r))
      except: pass
    cand.sort(key=lambda x:x[0])
    if cand:
      dt,label=cand[0]
      obj["next_special"]={"date":dt.isoformat(),"label":label}
except: pass

open(path,"w",encoding="utf-8").write(json.dumps(obj,ensure_ascii=False))
PY
  else
    echo "{\"ts\":\"$(ts)\",\"state\":\"$state\",\"reason\":\"$reason\"}" > "$MARKET_STATE_JSON" 2>/dev/null || true
  fi
}

# -------------------------
# Main
# -------------------------
CMD_STR="$*"
load_conf

# Kill switch
if [ -f "$KILL_SWITCH_FILE" ]; then
  write_market_state "DISABLED" "kill_switch" "file=$KILL_SWITCH_FILE"
  log_in "SKIPPED(disabled)" "kill_switch" "$CMD_STR"
  exit 0
fi

# Force run
if [ "$FORCE_RUN" = "1" ]; then
  write_market_state "FORCE" "force_run" "FORCE_RUN=1"
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
  elif in_time_ranges "$EXEMPT_TIME"; then
    REASON="exempt_time"
  else
    write_market_state "CLOSED" "outside_window" ""
    log_out "SKIPPED(outside_window)" "outside_hours" "$CMD_STR"
    exit 0
  fi

  # Publish state
  if [ "$REASON" = "exempt_schedule" ] || [ "$REASON" = "exempt_date" ]; then
    write_market_state "SPECIAL_ACTIVE" "$REASON" "${SPEC_RANGE:-}"
  elif [ "$REASON" = "normal_window" ]; then
    write_market_state "OPEN" "$REASON" ""
  else
    write_market_state "OPEN" "$REASON" ""
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

# Run
log_in "RUN" "age=${AGE_SEC:-na}s" "$CMD_STR"
exec "$@"
