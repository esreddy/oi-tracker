# Cron guard decision flow (`scripts/oi_cron_guard.sh`)

```
CRON (NIFTY every minute, BANKNIFTY every 3 minutes)
        |
        v
+---------------------+
| oi_cron_guard.sh    |
| (THE GATEKEEPER)    |
+---------------------+
        |
        v
┌──────────────────────────────────────────────┐
│ 0. Kill switch / force                        │
│    - writable/cache/oi_guard.KILL → SKIP      │
│    - FORCE_RUN=1 → skip steps 1 and 2         │
└──────────────────────────────────────────────┘
        |
        v
┌──────────────────────────────────────────────┐
│ 1. Time window (IST), first match wins        │
│    a) EXEMPT_SCHEDULE window active now       │
│    b) EXEMPT_DATES day, 09:15–15:30           │
│    c) Mon–Fri, 09:15–15:30                    │
│    d) EXEMPT_TIME window on Mon–Fri           │
│       (or on an EXEMPT_DATES day)             │
│    none → SKIP (outside window)               │
└──────────────────────────────────────────────┘
        |
        v
┌──────────────────────────────────────────────┐
│ 2. Holiday check (NSE holiday master)         │
│    - Segment filter: FO (HOLIDAY_SEGMENTS)    │
│    - COM ignored                              │
│    - Not for special sessions (a, b)          │
│    - IGNORE_HOLIDAY=1 disables it             │
│    holiday → SKIP                             │
└──────────────────────────────────────────────┘
        |
        v
┌──────────────────────────────────────────────┐
│ 3. Overlap lock (per LABEL)                   │
│    - Previous run still busy → SKIP           │
│    - Released when the job ends               │
└──────────────────────────────────────────────┘
        |
        v
┌──────────────────────────────────────────────┐
│ 4. Heartbeat (HEARTBEAT_SQL / HEARTBEAT_CMD)  │
│    - Age of the last snapshot                 │
│    - Frozen (age ≥ 300 s): run only every     │
│      3rd minute                               │
│    - ENRICH_REQUIRES_FRESH_FETCH=1:           │
│      age ≥ 120 s → SKIP (enrich waits)        │
└──────────────────────────────────────────────┘
        |
        v
┌──────────────────────────────────────────────┐
│ 5. Final Decision                             │
│    ├─ DRY_RUN=1 → log "would run", stop       │
│    └─ RUN command (exit code passed through)  │
└──────────────────────────────────────────────┘
        |
        v
+----------------------------+
| PHP Spark Command          |
| oi:fetch / oi:enrich       |
+----------------------------+
        |
        v
+----------------------------+
| Database (oi_snapshots,    |
| oi_metrics)                |
+----------------------------+
        |
        v
+----------------------------+
| Dashboard (read-only)      |
+----------------------------+



CRON
 └── triggers WHEN to try

oi_cron_guard.sh
 └── decides IF it should run

special_trading_days.conf
 └── tells WHAT days/times are special

Spark Commands
 └── decide HOW data is fetched/enriched

Database
 └── source of truth (heartbeat)

Dashboard
 └── observes, never controls
```

## Log lines

Decisions inside a window go to `writable/logs/market_window.log`, skips outside the window to
`writable/logs/market_outside.log` (`LOG_MODE=verbose` adds the command):

| Status | Meaning |
|---|---|
| `RUN` | ran the command |
| `RUN(force)` | `FORCE_RUN=1` |
| `DRYRUN(would_run)` | `DRY_RUN=1`; nothing ran |
| `SKIPPED(outside_window)` | not in any window |
| `SKIPPED(holiday)` | NSE holiday (FO) |
| `SKIPPED(overlap_lock)` | the previous run of this `LABEL` is still running |
| `SKIPPED(frozen_throttle)` | data frozen; waiting for the next throttled minute |
| `SKIPPED(enrich_fetch_delayed)` | enrich waiting for a fresh fetch |
| `SKIPPED(disabled)` | kill switch file present |

## Settings (environment variables)

| Variable | Default | Purpose |
|---|---|---|
| `LABEL` | `task` | job name in logs; lock name |
| `HEARTBEAT_SQL` | — | SQL returning the last snapshot's epoch seconds (run with the `mysql` client, credentials from `~/.my.cnf`); see crontab.example |
| `HEARTBEAT_CMD` | — | alternative: shell command printing the epoch seconds |
| `ENRICH_REQUIRES_FRESH_FETCH` | `0` | `1` for enrich jobs |
| `FETCH_STALE_SEC` | `120` | age at which enrich waits |
| `FROZEN_SEC` / `FROZEN_THROTTLE_MIN` | `300` / `3` | frozen-data throttle |
| `WINDOW_START` / `WINDOW_END` | `0915` / `1530` | Mon–Fri market window (IST) |
| `EXEMPT_TIME` | `09:07-09:10,15:44-15:47` | extra windows (pre-open / closing snapshots) |
| `EXEMPT_DATES`, `EXEMPT_SCHEDULE` | from `special_trading_days.conf` | special sessions |
| `HOLIDAY_SEGMENTS` | `FO` | holiday segments to honour (empty = all) |
| `HOLIDAY_IGNORE_SECTIONS` | `COM` | holiday sections to ignore |
| `HOLIDAY_REFRESH_DAYS` / `HOLIDAY_RETRY_MIN` | `7` / `30` | holiday cache refresh / retry after a failed refresh |
| `IGNORE_HOLIDAY` | `0` | `1` skips the holiday check |
| `DRY_RUN` | `0` | decide and log only |
| `FORCE_RUN` / `FORCE_RUN_NOLOCK` | `0` / `0` | skip window + holiday checks / also the lock |
| `KILL_SWITCH_FILE` | `writable/cache/oi_guard.KILL` | present = every guarded job skips |
| `LOG_MODE` / `DEBUG` | `compact` / `0` | log format / extra debug lines |
| `BASE_DIR` | `/Users/sudhakar/Herd/oi-tracker` | project folder (logs and cache live under it) |
