<?php
/**
 * WordPress test suite configuration.
 *
 * Loaded by the WP core test library (via WP_PHPUNIT__TESTS_CONFIG) both in the PHPUnit
 * process and in the `install.php` subprocess it spawns, so it must be self-contained.
 *
 * The database is a SQLite file provided by the SQLite Database Integration drop-in that
 * `bin/install-wp-tests.sh` installs - no MySQL server is needed.
 *
 * @package Serbian Addons for WooCommerce
 * @subpackage Tests
 */

// phpcs:disable WordPress.WP.GlobalVariablesOverride.Prohibited

$wcsrb_tests_dir = getenv( 'WCSRB_TESTS_DIR' ) ?: rtrim( sys_get_temp_dir(), '/' ) . '/wcsrb-wp-tests';
// One run directory per checkout, so parallel worktrees never share a database.
$wcsrb_run_dir = getenv( 'WCSRB_TESTS_RUN_DIR' ) ?: $wcsrb_tests_dir . '/runs/' . md5( dirname( __DIR__ ) );

if ( ! is_dir( $wcsrb_run_dir ) ) {
	mkdir( $wcsrb_run_dir, 0777, true );
}

define( 'ABSPATH', $wcsrb_tests_dir . '/wordpress/' );

// SQLite Database Integration.
define( 'DB_ENGINE', 'sqlite' );
define( 'DB_PATH', $wcsrb_run_dir . '/wordpress.sqlite' );

// Unused by SQLite, but required by the WP test library.
define( 'DB_NAME', 'wordpress_tests' );
define( 'DB_USER', 'root' );
define( 'DB_PASSWORD', '' );
define( 'DB_HOST', 'localhost' );
define( 'DB_CHARSET', 'utf8' );
define( 'DB_COLLATE', '' );

$table_prefix = 'wptests_';

define( 'WP_TESTS_DOMAIN', 'example.org' );
define( 'WP_TESTS_EMAIL', 'admin@example.org' );
define( 'WP_TESTS_TITLE', 'Test Blog' );
define( 'WP_PHP_BINARY', getenv( 'WP_PHP_BINARY' ) ?: ( 'phpdbg' === PHP_SAPI ? 'php' : PHP_BINARY ) );
define( 'WPLANG', '' );

define( 'WP_DEBUG', true );
define( 'FS_METHOD', 'direct' );

// Plugin specific.
define( 'WCRS_IPS_DIR', $wcsrb_run_dir . '/ips' );
