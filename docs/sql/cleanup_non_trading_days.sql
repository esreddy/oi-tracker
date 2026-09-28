-- One-time cleanup of day-wise rows built for non-trading days.
--
-- Why: before 2026-09, oi_cron_guard.sh also ran oi:fetch on Saturdays/Sundays in its
-- pre-open/post-close windows (09:09 and 15:45), storing Friday's data with a weekend
-- timestamp, and oi:daywise:update (09:10 daily) turned those into Sat/Sun rows. The
-- "Daily OI Bias History (Last 5 Days)" table then showed Friday's numbers several times.
-- The guard and the command no longer do this; this script removes the rows already stored.
--
-- IMPORTANT: keep real special sessions (e.g. a Budget Saturday or a Muhurat Sunday).
-- Put the dates listed in writable/cache/special_trading_days.conf (EXEMPT_DATES and the
-- dates in EXEMPT_SCHEDULE) into the NOT IN (...) lists below. Leave '1900-01-01' if none.
--
-- Run step 1, check the rows, then run step 2 in the same session.

-- 1) Preview
SELECT symbol, expiry_date, trade_date, DAYNAME(trade_date) AS day_name, day_bias, win_ce_oi, win_pe_oi
FROM oi_daywise_snapshots
WHERE (DAYOFWEEK(trade_date) IN (1, 7)                                          -- Sunday, Saturday
       OR trade_date IN (SELECT holiday_date FROM nse_holidays WHERE segment = 'FO'))
  AND trade_date NOT IN ('1900-01-01')                                          -- your special session dates
ORDER BY trade_date DESC, symbol;

-- 2) Delete the same rows
DELETE FROM oi_daywise_snapshots
WHERE (DAYOFWEEK(trade_date) IN (1, 7)
       OR trade_date IN (SELECT holiday_date FROM nse_holidays WHERE segment = 'FO'))
  AND trade_date NOT IN ('1900-01-01');                                         -- your special session dates

-- 3) OPTIONAL: the weekend snapshots themselves (Friday's data re-stamped).
--    They are numerically harmless, but take space. oi_snapshots.ts is UTC, so the
--    weekday is taken from the IST time.
-- SELECT COUNT(*) FROM oi_snapshots
--  WHERE DAYOFWEEK(CONVERT_TZ(ts, '+00:00', '+05:30')) IN (1, 7)
--    AND DATE(CONVERT_TZ(ts, '+00:00', '+05:30')) NOT IN ('1900-01-01');
-- DELETE FROM oi_snapshots
--  WHERE DAYOFWEEK(CONVERT_TZ(ts, '+00:00', '+05:30')) IN (1, 7)
--    AND DATE(CONVERT_TZ(ts, '+00:00', '+05:30')) NOT IN ('1900-01-01');
