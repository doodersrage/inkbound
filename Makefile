# Inkbound developer helpers (not shipped in the WordPress.org zip).

ROOT    := $(abspath $(dir $(lastword $(MAKEFILE_LIST))))
WP      := $(ROOT)/.wp-dev
PORT    ?= 38471
URL     ?= http://127.0.0.1:$(PORT)
VERSION := $(shell grep -E '^ \* Version:' $(ROOT)/inkbound.php | awk '{print $$3}')
OUT     ?= $(ROOT)/inkbound-$(VERSION).zip

.PHONY: zip setup serve sync-plugin

zip:
	@stage=$$(mktemp -d); \
	mkdir -p "$$stage/inkbound"; \
	rsync -a --exclude-from="$(ROOT)/distignore" --exclude='distignore' "$(ROOT)/" "$$stage/inkbound/"; \
	rm -f "$(OUT)"; \
	( cd "$$stage" && zip -qr "$(OUT)" inkbound ); \
	rm -rf "$$stage"; \
	echo "Wrote $(OUT)"

setup:
	@test -n "$$(command -v php)" || (echo "PHP is required." >&2; exit 1)
	@test -n "$$(command -v wp)" || (echo "WP-CLI is required (wp)." >&2; exit 1)
	@mkdir -p "$(WP)"
	@if [ ! -f "$(WP)/wp-load.php" ]; then \
		echo "Extracting WordPress…"; \
		tar -xzf /tmp/wordpress.tar.gz -C /tmp; \
		cp -a /tmp/wordpress/. "$(WP)/"; \
	fi
	@if [ ! -d "$(WP)/wp-content/plugins/sqlite-database-integration" ]; then \
		unzip -qo /tmp/sqlite-plugin.zip -d "$(WP)/wp-content/plugins"; \
	fi
	@printf '%s\n' "<?php" \
		"define( 'SQLITE_DB_DROPIN_VERSION', '1.8.0' );" \
		"if ( ! defined( 'DB_ENGINE' ) ) { define( 'DB_ENGINE', 'sqlite' ); }" \
		"\$$sqlite_plugin_implementation_folder_path = __DIR__ . '/plugins/sqlite-database-integration';" \
		"if ( file_exists( \$$sqlite_plugin_implementation_folder_path . '/wp-includes/sqlite/db.php' ) ) {" \
		"	require_once \$$sqlite_plugin_implementation_folder_path . '/wp-includes/sqlite/db.php';" \
		"}" > "$(WP)/wp-content/db.php"
	@if [ ! -f "$(WP)/wp-config.php" ]; then \
		wp config create --path="$(WP)" --dbname=inkbound --dbuser=inkbound --dbpass=inkbound --dbhost=localhost --skip-check --extra-php <<'PHP'; \
define( 'DB_ENGINE', 'sqlite' ); \
define( 'WP_DEBUG', true ); \
define( 'WP_DEBUG_LOG', true ); \
define( 'WP_DEBUG_DISPLAY', false ); \
PHP \
	fi
	@ln -sfn "$(ROOT)" "$(WP)/wp-content/plugins/inkbound"
	@if ! wp core is-installed --path="$(WP)" >/dev/null 2>&1; then \
		wp core install --path="$(WP)" --url="$(URL)" --title="Inkbound" \
			--admin_user=admin --admin_password=inkbound-demo --admin_email=admin@example.test --skip-email; \
	fi
	@wp option update blogdescription "Serial fiction on WordPress — chapters, follows, progress, and update mail." --path="$(WP)"
	@wp rewrite structure '/%postname%/' --hard --path="$(WP)"
	@wp plugin activate inkbound --path="$(WP)"
	@wp inkbound seed --path="$(WP)" || wp eval 'Inkbound_Seed::run();' --path="$(WP)"
	@wp rewrite flush --hard --path="$(WP)"
	@echo "WordPress is ready at $(URL)"
	@echo "Admin: $(URL)/wp-admin  user admin / inkbound-demo"
	@echo "Reader: user reader / reader-demo"

serve: setup
	@printf '%s\n' "<?php" \
		"\$$root = \$$_SERVER['DOCUMENT_ROOT'] ?? '';" \
		"\$$path = parse_url( \$$_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH );" \
		"\$$file = \$$root . \$$path;" \
		"if ( \$$path !== '/' && \$$path !== '' && file_exists( \$$file ) && ! is_dir( \$$file ) ) { return false; }" \
		"require \$$root . '/index.php';" > "$(WP)/inkbound-router.php"
	@echo "Inkbound preview: $(URL)"
	@cd "$(WP)" && exec php -S "0.0.0.0:$(PORT)" -t "$(WP)" "$(WP)/inkbound-router.php"

# Copy a Plugin Check–clean plugin tree (no .git / Makefile / docs) into DEST.
# Example: make sync-plugin DEST=/path/to/wp-content/plugins/inkbound
sync-plugin:
	@test -n "$(DEST)" || (echo "Usage: make sync-plugin DEST=/path/to/wp-content/plugins/inkbound" >&2; exit 1)
	@mkdir -p "$(DEST)"
	@rsync -a --delete --exclude-from="$(ROOT)/distignore" --exclude='distignore' --exclude='.git' --exclude='.gitignore' "$(ROOT)/" "$(DEST)/"
	@echo "Synced clean plugin files to $(DEST)"
