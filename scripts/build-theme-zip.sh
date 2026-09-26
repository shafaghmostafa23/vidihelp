#!/usr/bin/env bash
# Builds dist/vidiform-wordpress-theme.zip containing the vidiform/ theme directory.
set -euo pipefail
cd "$(dirname "$0")/.."
mkdir -p dist
rm -f dist/vidiform-wordpress-theme.zip
zip -rq dist/vidiform-wordpress-theme.zip vidiform -x '*.DS_Store' '*/.git*'
echo "dist/vidiform-wordpress-theme.zip"
