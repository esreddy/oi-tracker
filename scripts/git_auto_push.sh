#!/bin/bash
# Daily auto-commit + push (cron 17:30).
#
# Only files that are ALREADY tracked are committed (git add -u). New files are never
# added automatically, so secrets, dumps or ad-hoc backup copies can't be published by
# accident (this repository is public). Add genuinely new files by hand: git add <file>

cd /Users/sudhakar/Herd/oi-tracker || exit 1

ts() { date '+%Y-%m-%d %H:%M:%S'; }

# New files are skipped on purpose; say so, so they aren't forgotten
untracked=$(git ls-files --others --exclude-standard | wc -l | tr -d ' ')
if [ "$untracked" -gt 0 ]; then
    echo "[$(ts)] NOTE: $untracked untracked file(s) not auto-committed (git add them manually if intended)"
fi

# Check for changes to tracked files only
if [[ -z $(git status --porcelain --untracked-files=no) ]]; then
    echo "[$(ts)] No tracked changes; nothing to push."
    exit 0
fi

git add -u
git commit -m "Auto backup: $(ts)" || exit 1
git push || { echo "[$(ts)] ERROR: git push failed"; exit 1; }
echo "[$(ts)] Pushed."
