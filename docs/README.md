# 📊 OI Tracker – System Workflow & Architecture

A production-grade Open Interest (OI) tracking system for **NIFTY / BANKNIFTY**, built on **CodeIgniter 4**, CLI commands, cron guards, and a rich frontend enhancer.

This document explains **what runs when**, **which file does what**, and **which file is critical at startup**.

---

## 🧠 High-Level Architecture

```
NSE APIs
   ↓
Fetch / Snapshot / Enrich (CLI Commands)
   ↓
MySQL Tables (Snapshots, Metrics, EOD)
   ↓
Dashboard Controller
   ↓
OI Dashboard (PHP View)
   ↓
oi_track_enhancer.js (UI Intelligence)
```

---

## ⚠️ MOST IMPORTANT FILE (Startup / Gatekeeper)

### `oi_cron_guard.sh` ⭐⭐⭐

This is the **startup brain** of the entire system.

### What it does
- Controls **WHEN** any OI job can run
- Enforces:
  - Market hours (09:15–15:30 IST)
  - Special trading days (weekends / special NSE sessions)
  - NSE holidays (auto-fetched & cached)
  - DB heartbeat / frozen detection
  - Overlap protection (lock)
  - Kill-switch safety

### How it is used
Every cron **must wrap** the actual command:

```bash
oi_cron_guard.sh php spark oi:fetch
oi_cron_guard.sh php spark oi:enrich
```

❗ If this file blocks execution, **nothing else runs**.

---

## 🔁 Core Data Flow (Step-by-Step)

### 1️⃣ Fetch Raw OI (Live Market Data)

**File**
- `FetchOi.php` (CLI Command)

**Runs**
- Every 1–3 minutes during market hours

**Purpose**
- Calls NSE Option Chain API
- Fetches:
  - OI
  - Change in OI
  - Volume
  - LTP

**DB**
- `oi_snapshots`

---

### 2️⃣ Snapshot & Metrics Storage

**Models**
- `OiSnapshotModel.php`
- `OiMetricsModel.php`

**Purpose**
- Clean inserts
- Prevent duplicates
- Maintain consistent snapshot state

---

### 3️⃣ Enrich OI Data (Intelligence Layer)

**File**
- `OiEnrich.php` (CLI Command)

**Runs**
- After Fetch (heartbeat-guarded)

**Computes**
- ATM
- ΔOI windows (1m / 3m / 5m / 10m / 15m / 30m)
- CE vs PE dominance
- PCR
- Support / Resistance
- Max Pain
- Gamma Zone (if enabled)

**DB**
- `oi_metrics` + enriched snapshot tables

---

### 4️⃣ Daywise & EOD Processing

#### a) Daywise Aggregation
**File**
- `OiDaywiseUpdate.php`

**Purpose**
- Builds multi-day summaries
- Used in *“Last Few Days – OI Price Behavior”*

---

#### b) Index End-of-Day (Post Market)

**Files**
- `IndexEodUpdate.php`
- `IndexEodModel.php`

**Runs**
- Once after market close

**Stores**
- Daily close
- High / Low
- Trend tags (Bullish / Bearish)

---

### 5️⃣ NSE Holiday Management

**File**
- `FetchNseHolidays.php`

**Purpose**
- Fetches NSE holiday list
- Cached yearly
- Used by `oi_cron_guard.sh` to skip non-trading days

---

## 🖥️ Dashboard Flow

### Controller Layer
**File**
- `OiController.php`

**Role**
- Loads dashboard & APIs
- Fetches:
  - Latest snapshots
  - Enriched metrics
  - Daywise summaries
  - EOD context

---

### Dashboard View
**File**
- `oi_dashboard.php`

**Contains**
- Index Moves
- Put–Call Pressure
- Market Bias Meter
- Strike Heatmap
- OI Track Table
- NSE-style Option Chain
- ATM Battle Zone

---

### Frontend Intelligence (Very Important)

**File**
- `oi_track_enhancer.js`

**Runs**
- On page load
- After every data refresh

**Handles**
- ATM row detection
- ΔOI arrows & %
- Timeframe toggles
- Spike blink (3m vs 10m)
- CE/PE dominance shading
- ATM Summary Card
- Leaders / Signals / Regime logic

> ⚠️ This file **does not fetch data**  
> It only **interprets and visualizes backend data**

---

## 🧹 Maintenance & Pruning

### Monthly Cleanup
**File**
- `OiPrune.php`

**Purpose**
- Deletes old snapshots
- Keeps DB lean & fast
- Maintains partition health

---

## ⏱️ Complete Cron Execution Chain

```
CRON
 ↓
oi_cron_guard.sh   (Startup / Market Gate)
 ↓
FetchOi.php        (Raw data)
 ↓
OiEnrich.php       (Intelligence)
 ↓
OiDaywiseUpdate.php
 ↓
IndexEodUpdate.php
```

---

## 🧠 One-Line Debug Guide

- ❌ Nothing runs → check `oi_cron_guard.sh`
- ❌ Data missing → check `FetchOi.php`
- ❌ Wrong numbers → check `OiEnrich.php`
- ❌ UI issues → check `oi_track_enhancer.js`

---

## 📌 Notes

- Always test new logic with `DRY_RUN=1`
- Never bypass `oi_cron_guard.sh` in production
- Treat `oi_track_enhancer.js` as **presentation-only**

---

✅ **Document last updated:** Feb 2026
