#!/usr/bin/env bash
# Install a local WordPress + SQLite preview with Inkbound loaded.
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
WP="$ROOT/.wp-dev"
PORT="${INKB_PORT:-38471}"
URL="${INKB_URL:-http://127.0.0.1:${PORT}}"

if ! command -v php >/dev/null; then
  echo "PHP is required." >&2
  exit 1
fi
if ! command -v wp >/dev/null; then
  echo "WP-CLI is required (wp)." >&2
  exit 1
fi

mkdir -p "$WP"

if [[ ! -f "$WP/wp-load.php" ]]; then
  echo "Extracting WordPress…"
  tar -xzf /tmp/wordpress.tar.gz -C /tmp
  cp -a /tmp/wordpress/. "$WP/"
fi

if [[ ! -d "$WP/wp-content/plugins/sqlite-database-integration" ]]; then
  unzip -qo /tmp/sqlite-plugin.zip -d "$WP/wp-content/plugins"
fi

cat > "$WP/wp-content/db.php" << 'EOF'
<?php
define( 'SQLITE_DB_DROPIN_VERSION', '1.8.0' );
if ( ! defined( 'DB_ENGINE' ) ) {
	define( 'DB_ENGINE', 'sqlite' );
}
$sqlite_plugin_implementation_folder_path = __DIR__ . '/plugins/sqlite-database-integration';
if ( file_exists( $sqlite_plugin_implementation_folder_path . '/wp-includes/sqlite/db.php' ) ) {
	require_once $sqlite_plugin_implementation_folder_path . '/wp-includes/sqlite/db.php';
}
EOF

if [[ ! -f "$WP/wp-config.php" ]]; then
  wp config create \
    --path="$WP" \
    --dbname=inkbound \
    --dbuser=inkbound \
    --dbpass=inkbound \
    --dbhost=localhost \
    --skip-check \
    --extra-php << 'PHP'
define( 'DB_ENGINE', 'sqlite' );
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
define( 'WP_DEBUG_DISPLAY', false );
PHP
fi

ln -sfn "$ROOT" "$WP/wp-content/plugins/inkbound"

if ! wp core is-installed --path="$WP" >/dev/null 2>&1; then
  wp core install \
    --path="$WP" \
    --url="$URL" \
    --title="Inkbound" \
    --admin_user=admin \
    --admin_password=inkbound-demo \
    --admin_email=admin@example.test \
    --skip-email
fi

wp option update blogdescription "Serial fiction on WordPress — chapters, follows, progress, and update mail." --path="$WP"
wp rewrite structure '/%postname%/' --hard --path="$WP"
wp plugin activate inkbound --path="$WP"
wp inkbound seed --path="$WP" || wp eval 'Inkbound_Seed::run();' --path="$WP"
wp rewrite flush --hard --path="$WP"

echo "WordPress is ready at $URL"
echo "Admin: $URL/wp-admin  user admin / inkbound-demo"
echo "Reader: user reader / reader-demo"
