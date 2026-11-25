<?php
/**
 * TestCase class file.
 *
 * @package Serbian Addons for WooCommerce
 * @subpackage Tests
 */

namespace Oblak\WCSRB\Tests\Support;

use Oblak\WCSRB\Gateway\Gateway_Payment_Slip;
use WC_Order;

/**
 * Base integration test case.
 *
 * Every test runs inside a DB transaction that the WP test library rolls back, so option and
 * order changes never leak. In-memory state (gateway settings) and files on disk (IPS QR codes)
 * are reset here.
 */
abstract class TestCase extends \WP_UnitTestCase {
    /**
     * Set up the test.
     */
    public function set_up(): void {
        parent::set_up();

        // The DB (and, after parent::set_up(), the object cache) is back to the baseline -
        // re-read it into the long lived gateway instance a previous test may have changed.
        $this->gateway()->init_settings();
        \xwp_app( 'wcsrb' )->get( \Oblak\WCSRB\Services\Field_Validator::class )->reset();
        $this->clean_ips_dir();
    }

    /**
     * Tear down the test.
     */
    public function tear_down(): void {
        parent::tear_down();

        $this->clean_ips_dir();
    }

    /**
     * Get the payment slip gateway instance used by the plugin.
     *
     * @return Gateway_Payment_Slip
     */
    protected function gateway(): Gateway_Payment_Slip {
        return \wcsrb_slip_gw();
    }

    /**
     * Override gateway settings for the current test.
     *
     * @param  array<string,mixed> $settings Settings merged over the fixture defaults.
     * @return Gateway_Payment_Slip
     */
    protected function update_gateway_settings( array $settings ): Gateway_Payment_Slip {
        \update_option( 'woocommerce_wcsrb_payment_slip_settings', \array_merge( Fixtures::gateway_settings(), $settings ) );

        $gateway = $this->gateway();
        $gateway->init_settings();

        return $gateway;
    }

    /**
     * Create (and save) an order.
     *
     * Saving triggers `woocommerce_new_order`, i.e. the gateway stores the payment slip data and
     * generates the IPS QR code exactly like a real checkout.
     *
     * @param  array<string,mixed> $args {
     *     Optional. Order arguments.
     *
     *     @type 'person'|'company'  $type           Customer type. Default 'person'.
     *     @type string              $country        Billing country. Default 'RS'.
     *     @type string              $payment_method Payment method. Default 'wcsrb_payment_slip'.
     *     @type float               $total          Order total. Default 1234.5.
     *     @type string              $currency       Order currency. Default 'RSD'.
     *     @type string              $status         Order status. Default 'pending'.
     *     @type array<string,mixed> $props          Extra order props.
     *     @type array<string,mixed> $meta           Extra order meta.
     * }
     * @return WC_Order
     */
    protected function create_order( array $args = array() ): WC_Order {
        $args = \wp_parse_args(
            $args,
            array(
                'country'        => 'RS',
                'currency'       => 'RSD',
                'meta'           => array(),
                'payment_method' => 'wcsrb_payment_slip',
                'props'          => array(),
                'status'         => 'pending',
                'total'          => 1234.5,
                'type'           => 'person',
            ),
        );

        $order = new WC_Order();
        $order->set_props(
            \array_merge(
                array(
                    'billing_address_1'    => 'Bulevar oslobođenja 10',
                    'billing_city'         => 'Novi Sad',
                    'billing_country'      => $args['country'],
                    'billing_email'        => 'petar@example.org',
                    'billing_first_name'   => 'Petar',
                    'billing_last_name'    => 'Petrović',
                    'billing_phone'        => '0601234567',
                    'billing_postcode'     => '21000',
                    'currency'             => $args['currency'],
                    'payment_method'       => $args['payment_method'],
                    'payment_method_title' => 'Payment Slip',
                    'status'               => $args['status'],
                    'total'                => $args['total'],
                ),
                $args['props'],
            ),
        );

        $order->update_meta_data( '_billing_type', $args['type'] );

        if ( 'company' === $args['type'] ) {
            $order->set_billing_company( 'Firma d.o.o.' );
            $order->update_meta_data( '_billing_mb', Fixtures::MB );
            $order->update_meta_data( '_billing_pib', Fixtures::PIB );
        }

        foreach ( $args['meta'] as $key => $value ) {
            $order->update_meta_data( $key, $value );
        }

        $order->save();

        return \wc_get_order( $order->get_id() );
    }

    /**
     * Register the hooks of a handler that is restricted to wp-admin.
     *
     * The DI container evaluates handler contexts once (on `woocommerce_init`), when the test
     * process is not in wp-admin. This registers the handler's #[Action]/#[Filter] methods on the
     * container's instance the way the container would in wp-admin. Hooks are removed again after
     * the test by WP_UnitTestCase.
     *
     * @template T of object
     * @param  class-string<T> $classname Handler class.
     * @return T
     */
    protected function load_admin_handler( string $classname ): object {
        $handler = \xwp_app( 'wcsrb' )->get( $classname );

        foreach ( ( new \ReflectionClass( $classname ) )->getMethods( \ReflectionMethod::IS_PUBLIC ) as $method ) {
            foreach ( $method->getAttributes( \XWP\DI\Decorators\Filter::class, \ReflectionAttribute::IS_INSTANCEOF ) as $attribute ) {
                $args = $attribute->getArguments();

                \add_filter(
                    $args['tag'] ?? $args[0],
                    array( $handler, $method->getName() ),
                    (int) ( $args['priority'] ?? $args[1] ?? 10 ),
                    $method->getNumberOfParameters(),
                );
            }
        }

        return $handler;
    }

    /**
     * Remove generated IPS QR code files.
     */
    protected function clean_ips_dir(): void {
        foreach ( \glob( \WCRS_IPS_DIR . '/*.jpg' ) ?: array() as $file ) {
            \unlink( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink
        }
    }
}
