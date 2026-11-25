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
     * Plugin settings before set_core_setting() changed them.
     *
     * @var array<string,mixed>|null
     */
    private ?array $config_backup = null;

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

        if ( null !== $this->config_backup ) {
            $this->config_settings()->setValue( \xwp_app( 'wcsrb' )->get( \Oblak\WCSRB\Services\Config::class ), $this->config_backup );
            $this->config_backup = null;
        }

        $this->clean_ips_dir();
    }

    /**
     * Override a core plugin setting (Serbian Addons > General) for the current test.
     *
     * The Config service reads the settings once per request, like it does on a real site.
     *
     * @param  string $key   Setting key.
     * @param  mixed  $value Setting value, as the Config service returns it.
     */
    protected function set_core_setting( string $key, mixed $value ): void {
        $config = \xwp_app( 'wcsrb' )->get( \Oblak\WCSRB\Services\Config::class );
        $prop   = $this->config_settings();

        $this->config_backup ??= $prop->getValue( $config );

        $settings                 = $prop->getValue( $config );
        $settings['core'][ $key ] = $value;

        $prop->setValue( $config, $settings );
    }

    /**
     * Get the settings property of the Config service.
     *
     * @return \ReflectionProperty
     */
    private function config_settings(): \ReflectionProperty {
        $prop = new \ReflectionProperty( \Oblak\WCSRB\Services\Config::class, 'settings' );
        $prop->setAccessible( true );

        return $prop;
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
        return $this->register_hooks( \xwp_app( 'wcsrb' )->get( $classname ) );
    }

    /**
     * Register the #[Action]/#[Filter] methods of a handler instance.
     *
     * The DI container registers the hooks of a class only for its first instance, and
     * WP_UnitTestCase removes them after that test. Hooks already registered for this instance are kept.
     *
     * @template T of object
     * @param  T $handler Handler instance.
     * @return T
     */
    protected function register_hooks( object $handler ): object {
        foreach ( ( new \ReflectionClass( $handler ) )->getMethods( \ReflectionMethod::IS_PUBLIC ) as $method ) {
            foreach ( $method->getAttributes( \XWP\DI\Decorators\Filter::class, \ReflectionAttribute::IS_INSTANCEOF ) as $attribute ) {
                $args     = $attribute->getArguments();
                $tag      = $args['tag'] ?? $args[0];
                $callback = array( $handler, $method->getName() );

                // Proxied methods get extra arguments from the container - tests call those directly.
                if ( \XWP\DI\Interfaces\Can_Invoke::INV_PROXIED === ( $args['invoke'] ?? null ) || false !== \has_filter( $tag, $callback ) ) {
                    continue;
                }

                \add_filter( $tag, $callback, (int) ( $args['priority'] ?? $args[1] ?? 10 ), $method->getNumberOfParameters() );
            }
        }

        return $handler;
    }

    /**
     * Decode a QR code image like a (tolerant) scanner would.
     *
     * chillerlan's reader is a strict port of ZXing's detector and can misjudge the module size of
     * soft (anti-aliased, JPEG compressed) images that phones read without trouble. Retry with
     * grayscale / contrast pre-processing and, where available, the Imagick luminance source;
     * fail only when no configuration can read the code.
     *
     * @param  string $image Image blob or file path.
     * @return string        Decoded data.
     *
     * @throws \Throwable The last reader error when the code cannot be read.
     */
    protected function decode_qr( string $image ): string {
        $attempts = array(
            array(),
            array(
                'readerGrayscale'        => true,
                'readerIncreaseContrast' => true,
            ),
        );

        if ( \extension_loaded( 'imagick' ) ) {
            $attempts[] = array(
                'readerGrayscale'             => true,
                'readerIncreaseContrast'      => true,
                'readerUseImagickIfAvailable' => true,
            );
        }

        foreach ( $attempts as $options ) {
            $reader = new \chillerlan\QRCode\QRCode(
                new \chillerlan\QRCode\QROptions( $options + array( 'readerUseImagickIfAvailable' => false ) ),
            );

            try {
                // Binary image data always contains NUL bytes, file paths never do.
                return (string) ( \str_contains( $image, "\0" ) ? $reader->readFromBlob( $image ) : $reader->readFromFile( $image ) );
            } catch ( \Throwable $e ) {
                $error = $e;
            }
        }

        throw $error;
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
