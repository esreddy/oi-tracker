# 📊 OI Tracker – System Workflow & Architecture

An Open Interest (OI) tracking system for **NIFTY / BANKNIFTY**, built on **CodeIgniter 4**: CLI
commands collect and enrich NSE option-chain data under a cron guard, and one dashboard page
reads it through JSON endpoints.

This document explains **what runs when**, **which file does what**, and **where each number
comes from**. Setup, commands and endpoints: [../README.md](../README.md).

Times: `oi_snapshots.ts` and every other stored timestamp are **UTC**; market hours, trading
days and the dashboard use **IST** (Asia/Kolkata, a fixed +05:30).

---

## 🧠 High-Level Architecture

```
NSE option chain ──► oi:fetch ──────────► oi_snapshots ──► oi:enrich ─────────► oi_metrics
                                              │
                                              └──────────► oi:daywise:update ─► oi_daywise_snapshots
Yahoo chart API ───► index:eod:update ──► oi_index_eod
NSE holiday master ► oi:holidays ───────► nse_holidays
                                              │
                                              ▼
                       OiController / PriceController (JSON endpoints)
                                              ▼
                       oi_dashboard.php + oi_track_enhancer.js (browser)
```

Every market job (`oi:fetch`, `oi:enrich`) starts through `scripts/oi_cron_guard.sh`.

---

## ⚠️ Startup Gatekeeper: `scripts/oi_cron_guard.sh`

Cron starts the jobs every minute; the guard decides **whether each one runs now**:

- Market window: Mon–Fri 09:15–15:30 IST
- `EXEMPT_TIME`: extra windows (default 09:07–09:10 and 15:44–15:47) on trading weekdays
- Special sessions from `writable/cache/special_trading_days.conf`:
  `EXEMPT_DATES` (full days) and `EXEMPT_SCHEDULE` (date + time window)
- NSE holidays (FO segment by default) from an auto-refreshed, validated JSON cache
- Per-job lock (no overlapping runs of the same `LABEL`)
- DB heartbeat (`HEARTBEAT_SQL`): throttles jobs while data is frozen, and
  `ENRICH_REQUIRES_FRESH_FETCH=1` makes enrich wait for a fresh fetch
- Kill switch file, `FORCE_RUN`, `DRY_RUN`

```bash
LABEL="NIFTY_fetch" HEARTBEAT_SQL='...' /bin/bash scripts/oi_cron_guard.sh php spark oi:fetch NIFTY 5
```

The job's exit code is passed through. ❗ If the guard skips, nothing else runs — its decision
is in `writable/logs/market_window.log` or `market_outside.log`. Full decision order and
settings: [OI_SYSTEM_FLOW.md](OI_SYSTEM_FLOW.md).

---

## 🔁 Core Data Flow (Step-by-Step)

### 1️⃣ Fetch Raw OI (Live Market Data)

**File**
- `app/Commands/FetchOi.php` — `php spark oi:fetch <SYMBOL>`

**Runs**
- NIFTY every minute, BANKNIFTY every 3 minutes, through the guard

**Purpose**
- Reads the expiry list (NSE `option-chain-contract-info`) and the full chain of the current
  expiry (NSE `option-chain-v3`); keeps rows of the current or next expiry
- Stores per strike and side: OI, change in OI (NSE), volume, LTP, change in LTP, IV,
  bid/ask price and quantity, and the underlying price
- One run = one snapshot with one `ts` (UTC) for all its rows

**DB**
- `oi_snapshots` (the unique key `uniq_row (ts, symbol, expiry, strike, opt)` rejects duplicates)

---

### 2️⃣ Models

- `OiSnapshotModel` — `oi_snapshots` helpers used by enrich: latest snapshot, rows at a `ts`,
  nearest rows at/before a `ts`
- `OiMetricsModel` — `oi_metrics` (inserts, latest N rows)
- `IndexEodModel` — `oi_index_eod` (last stored date, upsert)

---

### 3️⃣ Enrich OI Data (Intelligence Layer)

**File**
- `app/Commands/OiEnrich.php` — `php spark oi:enrich <SYMBOL> [windowMinutes=10]`

**Runs**
- With each fetch, through the guard; skipped while the latest snapshot is stale

**Computes** (latest snapshot vs the nearest one `windowMinutes` earlier)
- PCR
- Biggest CE and PE OI walls: strike, OI and their change over the window
- Bias (Bullish / Bearish / Range-bound) from OI added around the ATM and the PCR
- Price vs OI: Long Build-up / Short Build-up / Short Covering / Long Unwinding
- Notes: breakout / breakdown risk when a wall unwinds

**DB**
- `oi_metrics`

Max pain, ΔOI per timeframe, walls near spot and the option-chain view are computed by the
dashboard endpoints (`OiController`) from `oi_snapshots`, not by enrich.

---

### 4️⃣ Daywise & EOD Processing

#### a) Daywise Aggregation
**File**
- `app/Commands/OiDaywiseUpdate.php` — `php spark oi:daywise:update --symbol=NIFTY --days=10`

**Runs**
- 15:50 Mon–Fri (after the 15:44–15:47 closing snapshots)

**Purpose**
- One row per IST day and expiry from that day's last snapshot: ATM ±5 aggregates, strongest
  strikes, PCR, day-over-day changes and a day bias → `oi_daywise_snapshots`
- Skips weekends and FO holidays (special sessions count); today only after the close
- Used by the *Daily OI Bias History* table and the expiry range box (via `/oi/track`)

---

#### b) Index End-of-Day (Post Market)

**Files**
- `app/Commands/IndexEodUpdate.php` — `php spark index:eod:update`
- `app/Models/IndexEodModel.php`
- `scripts/run_index_eod_update.sh` (launchd wrapper, see
  [../LAUNCHD_INDEX_EOD_SAFETY.md](../LAUNCHD_INDEX_EOD_SAFETY.md))

**Runs**
- Daily and at login (launchd)

**Stores**
- Daily open / high / low / close / previous close from the Yahoo chart API → `oi_index_eod`
- Gap-safe and idempotent: fills every missing day up to the latest trading day whose session
  has closed

**Used by**
- Index Moves and the Higher-Timeframe (month / YTD) panels

---

### 5️⃣ NSE Holidays & Special Sessions

- `app/Commands/FetchNseHolidays.php` — `php spark oi:holidays` (monthly) → `nse_holidays`,
  used by the dashboard (holiday list, market status, auto-refresh window) and by the commands
- The guard keeps its own copy of the NSE holiday master in
  `writable/cache/nse_holidays_YYYY.json`
- Special sessions: `writable/cache/special_trading_days.conf`, read by the guard and by
  `App\Libraries\TradingCalendar`

### Shared Libraries

- `App\Libraries\TradingCalendar` — IST trading days (weekends, FO holidays, special sessions),
  session end, latest closed trading day, UTC bounds of an IST day
- `App\Libraries\CliOptions` — `--name=value` / `--name value` options for the spark commands

---

## 🖥️ Dashboard Flow

### Controller Layer
**Files**
- `app/Controllers/OiController.php` — the `/oi` page and the `/oi/*` JSON endpoints
- `app/Controllers/PriceController.php` — `/price/breakouts`

**One refresh** (every 60 s during the trading-day window, or on Refresh), in this order; one
failing call does not stop the others, and overlapping refreshes are merged. While signals are
paused (stale or gapped data) and Safety mode is off, a refresh stops after `/oi/json`:

```
/oi/healthz → /oi/json → /oi/metrics → /oi/daywise → /oi/intraday → /oi/signals
            → /oi/json (next expiry, if shown) → /price/breakouts → /oi/track
```

---

### Dashboard View
**File**
- `app/Views/oi_dashboard.php`

**Contains**
- Status bar: DB / fetch / enrich freshness, market status, next holiday, Show last day (Safety
  mode), strikes ±, CE/PE filter, refresh timer
- Top dock: Σ expiry CE/PE OI with PCR, ATM CE/PE OI
- Breakouts (5m / 15m), Intraday Trend, Market Bias Meter, Strike OI Heatmap, Price Swing
  Structure, Put–Call Pressure Trend, Last 8 Classifications, Top Strikes Snapshot, Next Expiry
  Snapshot
- Index Moves and Higher-Timeframe Context (EOD)
- Last Few Days — OI & Price Behavior, Daily OI Bias History
- OI Track with the ATM summary card
- Live Option Chain (OI Synced), OI Concentration Zones
- Advanced: Strike Deltas (Delta by Strike + Net ΔOI)
- NSE Holidays (F&O)

---

### Frontend Intelligence

**File**
- `public/assets/js/oi_track_enhancer.js`

**Runs**
- After the OI Track table is rendered, after the other strike tables are re-rendered, and at
  the end of each refresh (no timer)

**Handles**
- ΔOI arrows & %, flat-change fading, Current OI intensity
- ATM row / ATM ±2 emphasis in the OI Track and the strike tables
- Spike blink (3m vs 10m)
- CE/PE dominance shading
- ATM summary card: CE/PE bars, Leaders, Key Levels, Signals, regime line

> ⚠️ This file **does not fetch data**
> It only **interprets and visualizes backend data**

---

## 🧹 Maintenance

| What | File | When |
|---|---|---|
| Delete old snapshots (chunks, logged to `oi_prune_logs`) | `app/Commands/OiPrune.php` — `oi:prune 1 --yes` | 1st of the month |
| Delete / rotate logs | `scripts/cleanup_logs.sh` | daily, after the close |
| Commit + push tracked files | `scripts/git_auto_push.sh` | daily |
| Backup of the project folder | `scripts/oi_tracker_backup.sh` | not in crontab.example |

---

## ⏱️ Schedule

The jobs are independent; each has its own cron (or launchd) entry:

| Job | When |
|---|---|
| `oi:fetch` NIFTY / BANKNIFTY (guarded) | every minute / every 3 minutes |
| `oi:enrich` NIFTY / BANKNIFTY (guarded) | every minute / every 3 minutes |
| `oi:daywise:update` | 15:50 Mon–Fri |
| `index:eod:update` | launchd: daily and at login |
| `oi:holidays` | 1st of the month, 11:30 |
| `oi:prune 1 --yes` | 1st of the month, 07:15 |

The exact lines (paths, timeouts, heartbeat) are in [`crontab.example`](crontab.example).
All `spark` commands exit with a non-zero code on failure, so cron/launchd can detect it.

---

## 🧠 One-Line Debug Guide

- ❌ Nothing runs → `writable/logs/market_window.log` / `market_outside.log` (guard decisions),
  kill switch `writable/cache/oi_guard.KILL`
- ❌ Data missing → `writable/logs/oi_fetch.log` (`FetchOi.php`)
- ❌ Wrong metrics / bias → `writable/logs/oi_enrich.log` (`OiEnrich.php`)
- ❌ Wrong numbers on the page → the JSON endpoint behind the panel (`OiController.php`)
- ❌ UI issues → browser console, `oi_track_enhancer.js`

---

## 📌 Notes

- Check guard decisions with `DRY_RUN=1` (logs what it would do, runs nothing)
- Never bypass `oi_cron_guard.sh` in production (`FORCE_RUN=1` only for manual runs)
- Treat `oi_track_enhancer.js` as **presentation-only**
- The dashboard's `/oi/track`, `/oi/daywise` and `/oi/intraday` rely on the `oi_snapshots`
  indexes from the migration (`uniq_row`, `sym_exp_ts`, `strike_idx`)

---

✅ **Document last updated:** Sep 2026
