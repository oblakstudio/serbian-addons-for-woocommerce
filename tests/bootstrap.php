<?php
/**
 * PHPUnit bootstrap: WordPress + WooCommerce + Serbian Addons for WooCommerce on SQLite.
 *
 * Environment:
 *   WCSRB_TESTS_DIR   Where bin/install-wp-tests.sh installed WordPress (default: $TMPDIR/wcsrb-wp-tests).
 *   WCSRB_TESTS_HPOS  1 (default) to store orders in HPOS tables, 0 for legacy post storage.
 *
 * @package Serbian Addons for WooCommerce
 * @subpackage Tests
 */

$wcsrb_root      = dirname( __DIR__ );
$wcsrb_tests_dir = getenv( 'WCSRB_TESTS_DIR' ) ?: rtrim( sys_get_temp_dir(), '/' ) . '/wcsrb-wp-tests';

if ( ! file_exists( $wcsrb_tests_dir . '/wordpress/wp-settings.php' ) || ! file_exists( $wcsrb_tests_dir . '/wordpress/wp-content/db.php' ) ) {
	fwrite( STDERR, "WordPress test environment not found in {$wcsrb_tests_dir}.\nRun: composer test:install\n" ); // phpcs:ignore
	exit( 1 );
}

require_once $wcsrb_root . '/vendor/yoast/phpunit-polyfills/phpunitpolyfills-autoload.php';

putenv( 'WP_PHPUNIT__TESTS_CONFIG=' . __DIR__ . '/wp-tests-config.php' );

$wcsrb_wp_tests_lib = getenv( 'WP_PHPUNIT__DIR' ) ?: $wcsrb_root . '/vendor/wp-phpunit/wp-phpunit';

require_once $wcsrb_wp_tests_lib . '/includes/functions.php';
require_once __DIR__ . '/Support/Fixtures.php';

define( 'WCSRB_TESTS_HPOS', '0' !== getenv( 'WCSRB_TESTS_HPOS' ) );

$GLOBALS['wp_tests_options'] = array(
	'active_plugins' => array(
		'woocommerce/woocommerce.php',
		'serbian-addons-for-woocommerce/serbian-addons-for-woocommerce.php',
	),
);

/**
 * Seed baseline store options and load WooCommerce + the plugin.
 *
 * Options are seeded before WooCommerce loads, so the payment slip gateway is valid and
 * enabled when WooCommerce initialises its gateways (that is when the gateway registers its hooks).
 */
tests_add_filter(
	'muplugins_loaded',
	static function () use ( $wcsrb_root ) {
		foreach ( \Oblak\WCSRB\Tests\Support\Fixtures::store_options() as $option => $value ) {
			update_option( $option, $value );
		}

		// Map the plugin directory to this checkout so plugin_basename() behaves like a regular install.
		$GLOBALS['wp_plugin_paths'][ wp_normalize_path( WP_PLUGIN_DIR . '/serbian-addons-for-woocommerce' ) ] = wp_normalize_path( $wcsrb_root );

		require_once WP_PLUGIN_DIR . '/woocommerce/woocommerce.php';
		require_once $wcsrb_root . '/serbian-addons-for-woocommerce.php';
	},
);

/**
 * Install WooCommerce (tables, roles, pages) and select the order storage under test.
 *
 * Runs once WooCommerce registered its post types (init:5), i.e. after Action Scheduler
 * initialised its data store (init:1).
 */
tests_add_filter(
	'woocommerce_after_register_post_type',
	static function () {
		WC_Install::install();

		// Reload capabilities after install, see https://core.trac.wordpress.org/ticket/28374.
		$GLOBALS['wp_roles'] = null; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		wp_roles();

		$sync = wc_get_container()->get( \Automattic\WooCommerce\Internal\DataStores\Orders\DataSynchronizer::class );

		if ( ! $sync->check_orders_table_exists() ) {
			$sync->create_database_tables();
		}

		update_option( 'woocommerce_custom_orders_table_enabled', wc_bool_to_string( WCSRB_TESTS_HPOS ) );
		update_option( 'woocommerce_custom_orders_table_data_sync_enabled', 'no' );
	},
);

require_once $wcsrb_wp_tests_lib . '/includes/bootstrap.php';

/*
 * WooCommerce initialises gateways and emails lazily, and the gateway registers its hooks at that
 * moment. WP_UnitTestCase restores the global hook state after every test, so hooks registered
 * during a test would be lost for the rest of the run - initialise both before the first test.
 */
WC()->payment_gateways();
WC()->mailer();
require_once __DIR__ . '/Support/TestCase.php';
