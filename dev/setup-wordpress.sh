#!/usr/bin/env bash
# Builds a throwaway WordPress test site (SQLite, no MySQL needed) with ConBoard activated and
# sample data, then serves it at http://localhost:8080 (login: admin / admin).
# Safe to re-run: it skips what already exists. Usage: bash dev/setup-wordpress.sh
set -euo pipefail

ROOT="${WP_TEST_DIR:-$HOME/wptest}"
PLUGIN_DIR="$(cd "$(dirname "$0")/.." && pwd)"
WP_DIR="$ROOT/wordpress"
CLI="php $ROOT/wp-cli.phar --allow-root"

mkdir -p "$ROOT"
cd "$ROOT"

[ -d "$WP_DIR" ] || { curl -sSL -o wp.zip https://wordpress.org/latest.zip && unzip -q wp.zip && rm wp.zip; }
[ -f wp-cli.phar ] || curl -sSL -o wp-cli.phar https://github.com/wp-cli/wp-cli/releases/download/v2.11.0/wp-cli-2.11.0.phar

cd "$WP_DIR/wp-content/plugins"
for p in sqlite-database-integration advanced-custom-fields; do
	[ -d "$p" ] || { curl -sSL -o "$p.zip" "https://downloads.wordpress.org/plugin/$p.latest-stable.zip" && unzip -q "$p.zip" && rm "$p.zip"; }
done
[ -e conboard ] || ln -s "$PLUGIN_DIR" conboard

cd "$WP_DIR"
if [ ! -f wp-content/db.php ]; then
	cp wp-content/plugins/sqlite-database-integration/db.copy wp-content/db.php
	sed -i "s#{SQLITE_IMPLEMENTATION_FOLDER_PATH}#$WP_DIR/wp-content/plugins/sqlite-database-integration#; s#{SQLITE_PLUGIN}#sqlite-database-integration/load.php#" wp-content/db.php
fi

[ -f wp-config.php ] || $CLI config create --skip-check --dbname=x --dbuser=x --dbpass=x --dbhost=localhost
if ! $CLI core is-installed 2>/dev/null; then
	$CLI core install --url=http://localhost:8080 --title=ConBoardTest --admin_user=admin --admin_password=admin --admin_email=a@example.com --skip-email
	# wp core install can guess the address from the folder layout; pin it
	$CLI option update home http://localhost:8080
	$CLI option update siteurl http://localhost:8080
	$CLI plugin activate advanced-custom-fields conboard
	$CLI rewrite structure '/%postname%/'
	$CLI eval-file "$PLUGIN_DIR/dev/seed.php"
fi

if ! curl -s -o /dev/null http://localhost:8080/; then
	nohup php -S localhost:8080 -t "$WP_DIR" > "$ROOT/server.log" 2>&1 &
	sleep 2
fi
echo "Ready: http://localhost:8080  (admin / admin)"
