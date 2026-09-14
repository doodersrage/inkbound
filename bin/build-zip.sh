#!/usr/bin/env bash
# Build a WordPress.org-ready zip of the plugin (repo root = plugin root).
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
VERSION="$(grep -E '^ \* Version:' "$ROOT/inkbound.php" | awk '{print $3}')"
OUT="${1:-$ROOT/inkbound-${VERSION}.zip}"
STAGE="$(mktemp -d)"
DEST="$STAGE/inkbound"

mkdir -p "$DEST"

# Copy plugin files, honoring .distignore patterns.
rsync -a \
  --exclude-from="$ROOT/.distignore" \
  --exclude='.distignore' \
  "$ROOT/" "$DEST/"

rm -f "$OUT"
(
  cd "$STAGE"
  zip -qr "$OUT" inkbound
)

rm -rf "$STAGE"
echo "Wrote $OUT"
