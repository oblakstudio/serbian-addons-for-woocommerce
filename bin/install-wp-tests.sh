#!/usr/bin/env bash
#
# Installs the environment used by the PHPUnit integration suite:
#   - WordPress core
#   - WooCommerce
#   - SQLite Database Integration (as the wp-content/db.php drop-in, so no MySQL is needed)
#
# The install lives OUTSIDE the plugin checkout (default: $TMPDIR/wcsrb-wp-tests) so that
# tools scanning the plugin directory (make-pot, linters, IDEs) never see WP core/WooCommerce.
# The plugin itself is loaded straight from the checkout by tests/bootstrap.php.
#
# Usage: bin/install-wp-tests.sh [--force]
#
# Environment:
#   WCSRB_TESTS_DIR  Install directory      (default: ${TMPDIR:-/tmp}/wcsrb-wp-tests)
#   WP_VERSION       WordPress version      (default: latest)
#   WC_VERSION       WooCommerce version    (default: latest-stable)
#   SQLITE_VERSION   SQLite plugin version  (default: latest-stable)

set -euo pipefail

TESTS_DIR="${WCSRB_TESTS_DIR:-${TMPDIR:-/tmp}/wcsrb-wp-tests}"
WP_VERSION="${WP_VERSION:-latest}"
WC_VERSION="${WC_VERSION:-latest-stable}"
SQLITE_VERSION="${SQLITE_VERSION:-latest-stable}"

WP_DIR="$TESTS_DIR/wordpress"
DL_DIR="$TESTS_DIR/downloads"
PLUGINS_DIR="$WP_DIR/wp-content/plugins"
STAMP="$TESTS_DIR/.installed"
WANT="wp=$WP_VERSION wc=$WC_VERSION sqlite=$SQLITE_VERSION"

if [[ "${1:-}" != "--force" && -f "$STAMP" && "$(cat "$STAMP")" == "$WANT" ]]; then
	echo "WordPress test environment is up to date in $TESTS_DIR ($WANT)"
	exit 0
fi

download() {
	local url="$1" dest="$2"

	if [[ "${1:-}" == *latest* || ! -s "$dest" ]]; then
		echo "Downloading $url"
		curl -fsSL --retry 3 --retry-delay 2 -o "$dest.part" "$url"
		mv "$dest.part" "$dest"
	fi
}

extract() {
	local zip="$1" dest="$2" tmp

	tmp="$(mktemp -d "$TESTS_DIR/extract.XXXXXX")"
	unzip -q "$zip" -d "$tmp"
	rm -rf "$dest"
	mkdir -p "$(dirname "$dest")"
	mv "$tmp"/* "$dest"
	rm -rf "$tmp"
}

mkdir -p "$DL_DIR"

if [[ "$WP_VERSION" == "latest" ]]; then
	WP_URL="https://wordpress.org/latest.zip"
else
	WP_URL="https://wordpress.org/wordpress-$WP_VERSION.zip"
fi

download "$WP_URL" "$DL_DIR/wordpress-$WP_VERSION.zip"
download "https://downloads.wordpress.org/plugin/woocommerce.$WC_VERSION.zip" "$DL_DIR/woocommerce-$WC_VERSION.zip"
download "https://downloads.wordpress.org/plugin/sqlite-database-integration.$SQLITE_VERSION.zip" "$DL_DIR/sqlite-$SQLITE_VERSION.zip"

extract "$DL_DIR/wordpress-$WP_VERSION.zip" "$WP_DIR"
extract "$DL_DIR/woocommerce-$WC_VERSION.zip" "$PLUGINS_DIR/woocommerce"
extract "$DL_DIR/sqlite-$SQLITE_VERSION.zip" "$PLUGINS_DIR/sqlite-database-integration"

# Install the SQLite drop-in, pointing it at the plugin copy.
SQLITE_PLUGIN_DIR="$PLUGINS_DIR/sqlite-database-integration"
sed \
	-e "s#{SQLITE_IMPLEMENTATION_FOLDER_PATH}#$SQLITE_PLUGIN_DIR#g" \
	-e "s#{SQLITE_PLUGIN}#sqlite-database-integration/load.php#g" \
	"$SQLITE_PLUGIN_DIR/db.copy" > "$WP_DIR/wp-content/db.php"

echo "$WANT" > "$STAMP"

echo "WordPress test environment installed in $TESTS_DIR"
echo "  WordPress:   $(grep -oP "wp_version = '\K[^']+" "$WP_DIR/wp-includes/version.php")"
echo "  WooCommerce: $(grep -oP '^\s*\* Version:\s*\K\S+' "$PLUGINS_DIR/woocommerce/woocommerce.php")"
echo "  SQLite:      $(grep -oP '^\s*\* Version:\s*\K\S+' "$SQLITE_PLUGIN_DIR/load.php")"
