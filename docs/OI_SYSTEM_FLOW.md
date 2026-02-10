CRON (every minute / 3 minutes)
        |
        v
+---------------------+
| oi_cron_guard.sh    |
| (THE GATEKEEPER)    |
+---------------------+
        |
        v
┌──────────────────────────────────────────┐
│ 1. Time & Date Resolution (IST)           │
│    - Weekday?                             │
│    - Market window 09:15–15:30?           │
│    - EXEMPT_TIME (daily extra windows)?   │
│    - EXEMPT_DATES (full-day specials)?    │
│    - EXEMPT_SCHEDULE (date+time)?         │
└──────────────────────────────────────────┘
        |
        v
┌──────────────────────────────────────────┐
│ 2. Holiday Check (NSE)                    │
│    - Segment filter (FO/CM)               │
│    - Ignore COM                           │
│    - Skip if holiday unless special day  │
└──────────────────────────────────────────┘
        |
        v
┌──────────────────────────────────────────┐
│ 3. Overlap Lock                           │
│    - Prevent duplicate execution         │
└──────────────────────────────────────────┘
        |
        v
┌──────────────────────────────────────────┐
│ 4. Heartbeat Check (DB)                   │
│    - Last snapshot time                  │
│    - Age calculation                     │
│    - Frozen throttle                     │
│    - Enrich waits for fresh fetch        │
└──────────────────────────────────────────┘
        |
        v
┌──────────────────────────────────────────┐
│ 5. Final Decision                        │
│    ├─ RUN command                        │
│    ├─ SKIP (outside window)              │
│    ├─ SKIP (holiday)                     │
│    ├─ SKIP (frozen / stale)              │
│    └─ DRY_RUN                            │
└──────────────────────────────────────────┘
        |
        v
+----------------------------+
| PHP Spark Command          |
| oi:fetch / oi:enrich       |
+----------------------------+
        |
        v
+----------------------------+
| Database (oi_snapshots)    |
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

