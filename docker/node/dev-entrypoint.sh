#!/bin/sh
set -eu

chown -R node:node /var/www/html/node_modules

lock_hash="$(sha256sum package-lock.json | cut -d ' ' -f 1)"
installed_hash="$(cat node_modules/.salesflow-lock-hash 2>/dev/null || true)"

if [ "$lock_hash" != "$installed_hash" ]; then
    su node -s /bin/sh -c 'npm ci --no-audit --no-fund --include=optional --libc=musl'
    printf '%s' "$lock_hash" > node_modules/.salesflow-lock-hash
    chown node:node node_modules/.salesflow-lock-hash
fi

exec su node -s /bin/sh -c 'npm run dev -- --host 0.0.0.0'
