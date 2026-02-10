#!/bin/bash

cd /Users/sudhakar/Herd/oi-tracker || exit 1

# Check if any changes exist
if [[ -n $(git status --porcelain) ]]; then
    git add .
    git commit -m "Auto backup: $(date '+%Y-%m-%d %H:%M:%S')"
    git push
fi
