#!/usr/bin/env bash
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
WP="$ROOT/.wp-dev"
PORT="${INKB_PORT:-38471}"

if [[ ! -f "$WP/wp-load.php" ]]; then
  "$ROOT/bin/setup-wp.sh"
fi

cd "$WP"
echo "Inkbound preview: http://127.0.0.1:${PORT}"
exec php -S "0.0.0.0:${PORT}" -t "$WP" "$ROOT/bin/router.php"
