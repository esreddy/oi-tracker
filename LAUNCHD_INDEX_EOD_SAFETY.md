# Launchd Safety Doc – Index EOD Updater (Mac)

## Job details

- Label: com.oi.indexeod.update
- Plist: ~/Library/LaunchAgents/com.oi.indexeod.update.plist
- Wrapper: /Users/sudhakar/Herd/oi-tracker/scripts/run_index_eod_update.sh
- Command: php spark index:eod:update

## Logs

- Main log:
  /Users/sudhakar/Herd/oi-tracker/writable/logs/index_eod_update.log
- Launchd out:
  /Users/sudhakar/Herd/oi-tracker/writable/logs/index_eod_launchd.out
- Launchd err:
  /Users/sudhakar/Herd/oi-tracker/writable/logs/index_eod_launchd.err

## IMPORTANT behavior

- Runs daily at configured time (StartCalendarInterval)
- Runs once when you login (RunAtLoad)
- Gap-safe: if Mac was OFF for days, next run fills missing days.
- Wrapper auto-disables job after repeated failures (MAX_FAILS).

---

## Useful commands

### Check plist exists

ls -l ~/Library/LaunchAgents/com.oi.indexeod.update.plist

### Validate plist syntax

plutil -lint ~/Library/LaunchAgents/com.oi.indexeod.update.plist

### Enable (load) job

launchctl load ~/Library/LaunchAgents/com.oi.indexeod.update.plist

### Disable (unload) job

launchctl unload ~/Library/LaunchAgents/com.oi.indexeod.update.plist

### Start job now (test without reboot)

launchctl start com.oi.indexeod.update

### Kickstart (simulate RunAtLoad / stronger start)

launchctl kickstart -k gui/$(id -u)/com.oi.indexeod.update

### Check if loaded

launchctl list | grep com.oi.indexeod.update

### Stop a running job (if needed)

launchctl stop com.oi.indexeod.update

---

## Troubleshooting

### No logs created

1. Confirm wrapper exists + executable:
   ls -l /Users/sudhakar/Herd/oi-tracker/scripts/run_index_eod_update.sh
   chmod +x /Users/sudhakar/Herd/oi-tracker/scripts/run_index_eod_update.sh

2. Run wrapper manually:
   /Users/sudhakar/Herd/oi-tracker/scripts/run_index_eod_update.sh
   tail -n 50 /Users/sudhakar/Herd/oi-tracker/writable/logs/index_eod_update.log

3. Load + start launchd:
   launchctl unload ~/Library/LaunchAgents/com.oi.indexeod.update.plist 2>/dev/null || true
   launchctl load ~/Library/LaunchAgents/com.oi.indexeod.update.plist
   launchctl start com.oi.indexeod.update

4. Check launchd err:
   tail -n 80 /Users/sudhakar/Herd/oi-tracker/writable/logs/index_eod_launchd.err

### Job auto-disabled due to failures

- Check main log for: "FAILSAFE: disabling launchd job..."
- Fix issue, then re-enable:
  launchctl load ~/Library/LaunchAgents/com.oi.indexeod.update.plist
  launchctl start com.oi.indexeod.update
