<?php
/**
 * BootstrapTest class file.
 *
 * @package Serbian Addons for WooCommerce
 * @subpackage Tests
 */

namespace Oblak\WCSRB\Tests\Integration;

use Automattic\WooCommerce\Utilities\OrderUtil;
use Oblak\WCSRB\Gateway\Gateway_Payment_Slip;
use Oblak\WCSRB\Tests\Support\TestCase;

/**
 * Smoke tests for the test environment itself.
 */
class BootstrapTest extends TestCase {
    public function test_database_is_sqlite(): void {
        $this->assertSame( 'sqlite', \DB_ENGINE );
        $this->assertInstanceOf( \WP_SQLite_DB::class, $GLOBALS['wpdb'] );
    }

    public function test_woocommerce_is_loaded_and_installed(): void {
        $this->assertSame( 1, \did_action( 'woocommerce_loaded' ) );
        $this->assertNotEmpty( \WC()->version );
        $this->assertTrue( \wc_get_container()->get( \Automattic\WooCommerce\Internal\DataStores\Orders\DataSynchronizer::class )->check_orders_table_exists() );
    }

    public function test_order_storage_matches_environment(): void {
        $this->assertSame( \WCSRB_TESTS_HPOS, OrderUtil::custom_orders_table_usage_is_enabled() );
    }

    public function test_plugin_app_is_booted(): void {
        $this->assertInstanceOf( \DI\Container::class, \xwp_app( 'wcsrb' ) );
        $this->assertSame( 'serbian-addons-for-woocommerce/serbian-addons-for-woocommerce.php', \WCRS_PLUGIN_BASE );
    }

    public function test_payment_slip_gateway_is_registered_and_available(): void {
        $gateways = \WC()->payment_gateways()->payment_gateways();

        $this->assertArrayHasKey( 'wcsrb_payment_slip', $gateways );
        $this->assertInstanceOf( Gateway_Payment_Slip::class, $gateways['wcsrb_payment_slip'] );
        $this->assertSame( $this->gateway(), $gateways['wcsrb_payment_slip'] );
        $this->assertTrue( $this->gateway()->is_available() );
    }

    public function test_order_fixture_runs_the_payment_slip_pipeline(): void {
        $order = $this->create_order();

        $this->assertTrue( \wcsrb_order_has_slip( $order ) );
        $this->assertIsArray( $order->get_meta( '_wcsrb_payment_data' ) );
        $this->assertFileExists( \xwp_app( 'wcsrb' )->get( \Oblak\WCSRB\Services\QR_Code_Manager::class )->get_filename( $order ) );
    }
}
