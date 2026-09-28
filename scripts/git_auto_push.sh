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

# Commit changes to tracked files only
if [[ -n $(git status --porcelain --untracked-files=no) ]]; then
    git add -u
    git commit -m "Auto backup: $(ts)" || exit 1
fi

# Push anything not yet on the remote (new auto-commit, or earlier manual commits/merges)
ahead=$(git rev-list --count '@{u}..HEAD' 2>/dev/null || echo 1)
if [ "$ahead" -eq 0 ]; then
    echo "[$(ts)] Nothing to push."
    exit 0
fi

git push || { echo "[$(ts)] ERROR: git push failed"; exit 1; }
echo "[$(ts)] Pushed $ahead commit(s)."
