# OI Tracker

Open-interest tracker for **NIFTY** and **BANKNIFTY** options on NSE, built with CodeIgniter 4.
Cron jobs save the option chain every minute during market hours and derive metrics from it;
a single dashboard page (`/oi`) shows the chain, OI changes, PCR, walls, day-wise behaviour and
index moves.

- Architecture and data flow: [docs/README.md](docs/README.md)
- Cron guard decision flow: [docs/OI_SYSTEM_FLOW.md](docs/OI_SYSTEM_FLOW.md)
- Recommended crontab: [docs/crontab.example](docs/crontab.example)
- Index EOD job (launchd): [LAUNCHD_INDEX_EOD_SAFETY.md](LAUNCHD_INDEX_EOD_SAFETY.md)

Times: the database stores UTC (`app.appTimezone` is `UTC`); the dashboard and the cron guard
work in IST (Asia/Kolkata).

---

## Requirements

- PHP 8.1+ with `intl`, `mbstring`, `mysqli` (mysqlnd), `curl`, `json`
- MySQL 8 (the queries are also tested on MariaDB 10.11), `utf8mb4`
- Composer
- For the cron jobs (macOS): `bash`, `python3` (the guard parses the NSE holiday JSON with it),
  `gtimeout` from coreutils (`brew install coreutils`), the `mysql` client for the guard's heartbeat

The app has **no login**. Run it on localhost or a trusted network only.

## Setup

1. Install dependencies:
   ```bash
   composer install
   ```
2. Create `.env` from the template and fill in the database (`app/Config/Database.php` holds no
   credentials):
   ```bash
   cp env .env
   ```
   ```ini
   # example values
   CI_ENVIRONMENT = production
   app.baseURL = 'http://oi-tracker.test/'
   database.default.hostname = localhost
   database.default.database = oi_tracker
   database.default.username = oi_user
   database.default.password = "your password"   # quote values with spaces or '#'
   ```
   `.env` is git-ignored; never commit credentials (this repository is public).
3. Point the web server's document root at `public/` (e.g. Laravel Herd). `/` redirects to `/oi`.
4. Make sure PHP can write to `writable/` (logs, cache, sessions).
5. Database: see [Database](#database). `php spark migrate` creates `oi_snapshots` and
   `oi_metrics` only, and the `oi_snapshots` migration lacks six columns that `oi:fetch` writes,
   so a new database cannot be built from the migrations alone yet.
6. For the cron guard's heartbeat, put the MySQL credentials and database in `~/.my.cnf`
   (the `mysql` client reads them; nothing is passed on the command line):
   ```ini
   [client]
   user=oi_user
   password="your password"

   [mysql]
   database=oi_tracker
   ```
7. Schedule the jobs: install the crontab from [docs/crontab.example](docs/crontab.example) and the
   launchd job for `index:eod:update` ([LAUNCHD_INDEX_EOD_SAFETY.md](LAUNCHD_INDEX_EOD_SAFETY.md)).
   The paths in both are for `/Users/sudhakar/Herd/oi-tracker`.
8. First data:
   ```bash
   php spark oi:holidays          # NSE holiday list for the dashboard
   php spark index:eod:update     # first run backfills one year of index EOD
   ```

## Special sessions and holidays

`writable/cache/special_trading_days.conf` (shell syntax) lists sessions outside the normal
calendar. The cron guard sources it; the dashboard and the CLI commands read `EXEMPT_DATES` and
`EXEMPT_SCHEDULE` from it (`App\Libraries\TradingCalendar`).

```bash
EXEMPT_DATES="2026-02-01"                        # full-day sessions (09:15–15:30 IST), e.g. a Budget Saturday
EXEMPT_SCHEDULE="2026-11-08@18:00-19:15"         # timed sessions (date@HH:MM-HH:MM), e.g. Muhurat trading
# EXEMPT_TIME="09:07-09:10,15:44-15:47"          # guard only: extra daily windows (default shown)
```

Holidays come from two places:

| Used by | Source | Filled by |
|---|---|---|
| Cron guard | `writable/cache/nse_holidays_YYYY.json` (NSE holiday master, FO segment) | the guard itself, refreshed every 7 days |
| Dashboard, `oi:daywise:update`, `index:eod:update` | table `nse_holidays` | `php spark oi:holidays` (monthly cron) |

## Commands

All commands exit with `0` on success and `1` on failure (`oi:daywise:update` and
`index:eod:update` return `7` for invalid options), so cron and launchd can detect failures.
Options work as `--name=value` or `--name value`.

| Command | What it does | Schedule (crontab.example) |
|---|---|---|
| `oi:fetch <SYMBOL> [ignored]` | Fetches the NSE option chain for the current expiry (rows of the current or next expiry are kept) and inserts one snapshot (one `ts`, UTC) into `oi_snapshots`. The second argument is ignored (kept for old crons). | NIFTY every minute, BANKNIFTY every 3 minutes, through the guard |
| `oi:enrich [SYMBOL] [windowMinutes=10]` | From the latest snapshot: PCR, biggest CE/PE OI walls and their change over the window, bias, price-vs-OI class → `oi_metrics`. | With fetch, through the guard (`ENRICH_REQUIRES_FRESH_FETCH=1`) |
| `oi:daywise:update [--symbol=NIFTY] [--days=10] [--expiry=] [--date=] [--dry-run]` | Day-wise EOD rows per IST day and expiry (ATM ±5 aggregates, strongest strikes, PCR, day-over-day changes) → `oi_daywise_snapshots`. Skips weekends and FO holidays; today only after the close. | 15:50 Mon–Fri |
| `index:eod:update [--from=] [--to=] [--symbol=]` | Daily index candles from the Yahoo chart API → `oi_index_eod`. Gap-safe and idempotent: from the day after the last stored date (or one year back) to the latest trading day whose session has closed. | launchd, daily and at login |
| `oi:holidays [SEGMENTS]` | NSE holiday master → `nse_holidays` (optional segment filter, e.g. `CM,FO`). | 1st of the month, 11:30 |
| `oi:prune [months] [--symbol=] [--dry-run] [--chunk=50000] [--yes] [--logs-keep-months=6] [--optimize-threshold=100000]` | Deletes `oi_snapshots` rows older than the first day of (current month − months), in chunks; logs to `oi_prune_logs`. `--yes` is required without a terminal. | 1st of the month, 07:15, `1 --yes` |

### The cron guard (`scripts/oi_cron_guard.sh`)

Every market job runs through the guard, which decides whether to run *now*: market window
(Mon–Fri 09:15–15:30 IST), extra windows, special sessions, FO holidays, a per-job lock, the DB
heartbeat, a kill switch and dry runs. The command's exit code is passed through. Details and
all settings: [docs/OI_SYSTEM_FLOW.md](docs/OI_SYSTEM_FLOW.md).

```bash
touch writable/cache/oi_guard.KILL      # stop all guarded jobs (delete the file to resume)
DRY_RUN=1 LABEL=test bash scripts/oi_cron_guard.sh php spark oi:fetch NIFTY   # log the decision only
FORCE_RUN=1 LABEL=test bash scripts/oi_cron_guard.sh php spark oi:fetch NIFTY # ignore window/holiday
```

Guard decisions are logged to `writable/logs/market_window.log` (inside the window) and
`writable/logs/market_outside.log` (outside it).

### Other scripts

| Script | Purpose |
|---|---|
| `scripts/cleanup_logs.sh` | Daily: deletes CodeIgniter logs older than 3 days, rotates job logs above 5 MB (keeps 8 gzipped copies). |
| `scripts/git_auto_push.sh` | Daily: commits changes to **tracked** files only, then pushes anything not yet pushed. New files are never added automatically. |
| `scripts/run_index_eod_update.sh` | launchd wrapper for `index:eod:update`: waits for internet, logs, disables the job after 5 consecutive failures. |
| `scripts/oi_tracker_backup.sh` | Daily/monthly `tar.gz` backup of the project folder to Google Drive (not part of crontab.example). |

## Dashboard

`/oi?symbol=NIFTY` (or `BANKNIFTY`).

- Refreshes every 60 s from 15 minutes before to 30 minutes after each session on trading days
  (special sessions included); paused on weekends and holidays. **Refresh** (or the `R` key)
  always works.
- Status bar: age of the last DB snapshot and of the fetch/enrich logs, refreshed from
  `/oi/healthz`; blinks after 5 minutes and plays one alarm after 8 minutes of stale data during
  market hours.
- **Show last day** (Safety mode): on, every panel keeps loading on stale or gapped data (e.g. to
  review the last session on a holiday); off, the signal panels pause while data is stale
  (≥ 15 min during market hours) or a day behind.
- Keys: `R` refresh, `1`/`2`/`3` strikes ±3/±5/±10, `E` next expiry.
- `public/assets/js/oi_track_enhancer.js` decorates the OI Track table (ΔOI arrows, ATM rows,
  spike blink, dominance shading, ATM summary card). It runs after each data refresh and table
  render; it never fetches data.

### HTTP endpoints (all GET, JSON unless noted)

| Path | Returns |
|---|---|
| `/oi` | The dashboard page (HTML). |
| `/oi/json?symbol=&window=10&expiry=` | Latest snapshot: price, ATM, PCR, bias, max pain, expiry totals, top strikes, and per-strike ΔOI over the window, since the day's first snapshot and as reported by NSE. |
| `/oi/track?symbol=&expiry=&lookbacks=1,5,15&strikes=5\|all` | OI Track / option chain rows: per strike and side current OI, OI and ΔOI per lookback, volume change since the day's first snapshot; walls, top OI, max pain (full chain). |
| `/oi/metrics?symbol=&limit=12` | Latest `oi_metrics` rows (limit 1–60). |
| `/oi/signals?symbol=` | Breakouts and swing high/low from today's underlying prices. |
| `/oi/daywise?symbol=&days=7&mode=front\|all&segment=FO` | Last N trading days: close, CE/PE OI and volume, PCR, changes, behaviour and strength. |
| `/oi/intraday?symbol=&mode=all\|front` | Today's first vs latest snapshot totals, strike shift (`oi_metrics`), 5-day baseline. |
| `/oi/expiries?symbol=` | Expiries in `oi_snapshots` from 14 days ago onwards (all, if there are none). |
| `/oi/healthz` | DB/fetch/enrich freshness for the status bar. |
| `/price/breakouts?symbol=&tf=5m&lookback=10` | Breakout signals from snapshot underlying prices. |
| `/oi/index-eod/export/{year}` | `oi_index_eod` rows of that year as CSV. |
| `/oi/cache/purge` | Clears the cached month/YTD figures, then redirects to `/oi`. |

## Database

| Table | Written by | Read by |
|---|---|---|
| `oi_snapshots` | `oi:fetch` | dashboard endpoints, `oi:enrich`, `oi:daywise:update`, guard heartbeat; pruned by `oi:prune` |
| `oi_metrics` | `oi:enrich` | `/oi/metrics`, `/oi/intraday` |
| `oi_daywise_snapshots` | `oi:daywise:update` | `/oi/track` (day-wise rows, expiry range box) |
| `oi_index_eod` | `index:eod:update` | dashboard index moves and month/YTD, CSV export |
| `nse_holidays` | `oi:holidays` | dashboard, `TradingCalendar` (commands) |
| `oi_prune_logs` | `oi:prune` | — |

`oi_day_summary`, if it exists, is no longer read or written by any code.

Migrations exist only for `oi_snapshots` and `oi_metrics`
(`app/Database/Migrations/`); the other tables and six `oi_snapshots` columns written by `oi:fetch`
(`chg_ltp, iv, bid_qty, bid_price, ask_price, ask_qty`) are not in a migration yet.

The dashboard queries rely on the `oi_snapshots` indexes from the migration:
`uniq_row (ts, symbol, expiry, strike, opt)` (unique), `sym_exp_ts (symbol, expiry, ts)` and
`strike_idx (symbol, expiry, strike, opt, ts)`. Check them with `SHOW INDEX FROM oi_snapshots;`.
With them, `/oi/track`, `/oi/daywise` and `/oi/intraday` answer in tens of milliseconds on
millions of rows.

`docs/sql/cleanup_non_trading_days.sql`: one-time, preview-then-delete SQL for day-wise rows that
older versions built for weekends and holidays (optionally also the weekend snapshots). Add your
special-session dates to its `NOT IN` lists first.

## Tests

```bash
composer test          # or: vendor/bin/phpunit
```

Unit tests cover `App\Libraries\CliOptions` and `App\Libraries\TradingCalendar`
(`tests/unit/`).

## Troubleshooting

| Symptom | Check |
|---|---|
| No new data | `writable/logs/market_window.log` / `market_outside.log` (guard decisions), `writable/cache/oi_guard.KILL`, `writable/logs/oi_fetch.log` |
| Fetch fails | NSE blocks or changes its API: `writable/logs/oi_fetch.log`; the NSE cookie is kept in `writable/nse_cookie.txt` |
| Wrong holiday/session handling | `writable/cache/special_trading_days.conf`, `writable/cache/nse_holidays_YYYY.json`, table `nse_holidays` |
| Status bar red / alarm | fetch or enrich log not written recently, or DB snapshots old (`/oi/healthz`) |
| Index moves missing | `writable/logs/index_eod_update.log`, [LAUNCHD_INDEX_EOD_SAFETY.md](LAUNCHD_INDEX_EOD_SAFETY.md) |
| Dashboard errors | browser console; `writable/logs/log-YYYY-MM-DD.log` |
