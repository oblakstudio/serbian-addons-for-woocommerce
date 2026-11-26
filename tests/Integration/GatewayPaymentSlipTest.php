<?php
/**
 * GatewayPaymentSlipTest class file.
 *
 * @package Serbian Addons for WooCommerce
 * @subpackage Tests
 */

namespace Oblak\WCSRB\Tests\Integration;

use Oblak\WCSRB\Gateway\Gateway_Payment_Slip;
use Oblak\WCSRB\Services\Config;
use Oblak\WCSRB\Tests\Support\Fixtures;
use Oblak\WCSRB\Tests\Support\TestCase;
use Oblak\WCSRB\Utils\Template_Extender;
use WC_Product_Simple;

/**
 * Payment slip gateway.
 */
class GatewayPaymentSlipTest extends TestCase {
    /**
     * Reset the request and screen.
     */
    public function tear_down(): void {
        $_GET     = array();
        $_POST    = array();
        $_REQUEST = array();
        \set_current_screen( 'front' );

        parent::tear_down();
    }

    public function test_gateway_is_registered_through_the_payment_gateways_filter(): void {
        $this->assertContains( $this->gateway(), \apply_filters( 'woocommerce_payment_gateways', array() ) );
    }

    public function test_base_props_and_raw_form_fields(): void {
        $gateway = $this->make_gateway();

        $this->assertSame( 'wcsrb_payment_slip', $gateway->id );
        $this->assertSame( 'Payment Slip', $gateway->method_title );
        $this->assertFalse( $gateway->has_fields );
        $this->assertSame( array( 'products' ), $gateway->supports );
        $this->assertArrayHasKey( 'bank_account', $gateway->get_form_fields() );
        $this->assertInstanceOf( \Closure::class, $gateway->get_form_fields()['bank_account']['options'], 'Closures are resolved only on the settings screen' );
    }

    public function test_form_fields_are_resolved_on_the_settings_screen(): void {
        $fields = $this->make_gateway_on_settings_screen()->get_form_fields();

        $this->assertSame(
            array(
                ''                         => 'Select bank account...',
                \wcsrb_get_serbian_banks()['160'] => array( Fixtures::BANK_ACCOUNT_SHORT => Fixtures::BANK_ACCOUNT_SHORT ),
            ),
            $fields['bank_account']['options'],
        );
        $this->assertSame( array( 'auto' => 'Automatic' ), $fields['payment_code']['options'] );
        $this->assertSame( array( 'readonly' => 'readonly' ), $fields['payment_code']['custom_attributes'], 'Only "auto" is available for both customer types' );
        $this->assertSame( array( 'auto', 'mod97' ), \array_keys( $fields['payment_model']['options'] ) );
        $this->assertStringContainsString( 'data-code="%order_id%"', $fields['payment_reference']['description'] );
        $this->assertSame(
            array(
                'data-auto'  => '%order_id%-%year%',
                'data-mod97' => '%mod97%-%order_id%-%year%',
            ),
            $fields['payment_reference']['custom_attributes'],
        );
        $this->assertSame( array( 'disabled' => 'disabled' ), $fields['qrcode_image']['custom_attributes'], 'No site icon' );
        $this->assertStringNotContainsString( 'Current image', $fields['qrcode_image']['description'] );
        $this->assertStringContainsString( 'Logs screen', $fields['debug']['description'] );
        $this->assertSame( 'Where to display the payment slip', $fields['display']['description'] );
        $this->assertSame( 'Where to display the QR Code', $fields['qrcode_shown']['description'] );
    }

    public function test_settings_screen_with_site_icon_and_order_number_filter(): void {
        \update_option( 'site_icon', $this->site_icon() );
        \add_filter( 'woocommerce_order_number', static fn( $n ) => 'WC-' . $n );

        $fields = $this->make_gateway_on_settings_screen()->get_form_fields();

        $this->assertSame( array(), $fields['qrcode_image']['custom_attributes'] );
        $this->assertStringContainsString( 'Current image: <img', $fields['qrcode_image']['description'] );
        $this->assertSame(
            array(
                'data-auto'  => '%order_number%',
                'data-mod97' => '%mod97%-%order_number%',
            ),
            $fields['payment_reference']['custom_attributes'],
        );
    }

    public function test_settings_normalisation(): void {
        $gateway = $this->update_gateway_settings(
            array(
                'display'      => 'order',
                'qrcode_image' => 'yes',
                'qrcode_shown' => 'yes',
            ),
        );

        $this->assertSame( array( 'order' ), $gateway->display );
        $this->assertSame( array( 'order', 'email' ), $gateway->qrcode_shown, 'Legacy "yes" means everywhere' );
        $this->assertFalse( $gateway->qrcode_image, 'No image without a site icon' );
        $this->assertSame( 'Test Prodavnica d.o.o.', $gateway->company['name'] );

        $this->assertSame( array(), $this->update_gateway_settings( array( 'qrcode_shown' => 'no' ) )->qrcode_shown );

        \update_option( 'site_icon', $this->site_icon() );

        $this->assertTrue( $this->update_gateway_settings( array( 'qrcode_image' => 'yes' ) )->qrcode_image );
    }

    public function test_gateway_hooks_are_loaded_only_when_enabled_and_valid(): void {
        $this->assertNotFalse( \has_action( 'woocommerce_thankyou_wcsrb_payment_slip' ), 'Bootstrapped gateway is enabled' );

        $gateway = $this->make_gateway( array( 'enabled' => 'no' ) );
        $before  = \has_action( 'woocommerce_new_order' );

        $gateway->init_gateway();

        $this->assertSame( $before, \has_action( 'woocommerce_new_order' ) );

        \update_option( 'woocommerce_currency', 'EUR' );
        $this->make_gateway()->init_gateway();

        $this->assertSame( $before, \has_action( 'woocommerce_new_order' ) );

        \update_option( 'woocommerce_currency', 'RSD' );
        $this->make_gateway()->init_gateway();

        $this->assertSame( $before, \has_action( 'woocommerce_new_order' ), 'An already loaded handler is not registered twice' );
    }

    public function test_init_action_fires_only_for_a_usable_gateway(): void {
        $initialized = array();
        \add_action(
            'wcsrb_payment_slip_init',
            static function ( $gw ) use ( &$initialized ) {
                $initialized[] = $gw;
            },
        );

        $this->make_gateway( array( 'enabled' => 'no' ) )->init_gateway();

        \update_option( 'woocommerce_currency', 'EUR' );
        $this->make_gateway()->init_gateway();

        \update_option( 'woocommerce_currency', 'RSD' );
        $gateway = $this->make_gateway();
        $gateway->init_gateway();

        $this->assertSame( array( $gateway ), $initialized );
    }

    public function test_visibility_field_helper_builds_the_location_multiselect(): void {
        $field = \wcsrb_format_gw_display_option( 'Where to show it' );

        $this->assertSame( 'multiselect', $field['type'] );
        $this->assertSame( 'Where to show it', $field['description'] );
        $this->assertSame( array( 'order', 'email' ), \array_keys( $field['options'] ), 'Store pages first' );
        $this->assertSame( array(), $field['default'] );
        $this->assertSame( 'wc-enhanced-select', $field['class'] );
    }

    public function test_qr_image_description_helper_shows_the_current_site_icon(): void {
        $without = \wcsrb_format_gw_qr_img_desc( 0 );

        \preg_match( '/<a [^>]*href="([^"]+)"/', $without, $link );
        \parse_str( (string) \wp_parse_url( \html_entity_decode( $link[1] ), \PHP_URL_QUERY ), $query );

        $this->assertSame( array( 'section' => 'title_tagline' ), $query['autofocus'] ?? null );
        $this->assertStringNotContainsString( 'Current image', $without );

        $icon = $this->site_icon();
        $with = \wcsrb_format_gw_qr_img_desc( $icon );

        $this->assertStringContainsString( 'Current image', $with );
        $this->assertStringContainsString( \wp_get_attachment_image_url( $icon, array( 16, 16 ) ), $with );
    }

    public function test_site_icon_link_opens_the_site_identity_customizer_section(): void {
        $description = $this->make_gateway_on_settings_screen()->get_form_fields()['qrcode_image']['description'];

        \preg_match( '/<a [^>]*href="([^"]+)"/', $description, $link );
        \parse_str( (string) \wp_parse_url( \html_entity_decode( $link[1] ), \PHP_URL_QUERY ), $query );

        $this->assertStringStartsWith( \admin_url( 'customize.php' ), \html_entity_decode( $link[1] ) );
        $this->assertSame( array( 'section' => 'title_tagline' ), $query['autofocus'] ?? null );
    }

    public function test_is_valid_for_use(): void {
        $this->assertTrue( $this->gateway()->is_valid_for_use() );

        \update_option( 'woocommerce_currency', 'EUR' );
        $this->assertSame( 'invalid_currency', $this->gateway()->is_valid_for_use()->get_error_code() );

        \update_option( 'woocommerce_currency', 'RSD' );
        \update_option( 'woocommerce_store_bank_accounts', array() );

        $gateway = \xwp_app( 'wcsrb' )->make( Gateway_Payment_Slip::class, array( 'config' => new Config() ) );

        $this->assertSame( 'invalid_bank_account', $gateway->is_valid_for_use()->get_error_code() );
        $this->assertTrue( $gateway->needs_setup() );
    }

    public function test_needs_setup(): void {
        $this->assertFalse( $this->gateway()->needs_setup() );
        $this->assertTrue( $this->update_gateway_settings( array( 'bank_account' => '' ) )->needs_setup() );
    }

    public function test_clearing_the_visibility_multiselects_is_saved(): void {
        $gateway = $this->gateway();

        $this->save_slip_settings( 'auto', '%order_id%-%year%' );
        $this->assertSame( array( 'order', 'email' ), $gateway->display, 'Fixture: shown everywhere' );

        // A cleared multiselect is not posted at all.
        unset( $_POST[ $gateway->get_field_key( 'display' ) ], $_POST[ $gateway->get_field_key( 'qrcode_shown' ) ] );
        $gateway->process_admin_options();
        $gateway->init_settings();

        $this->assertSame( array(), $gateway->display );
        $this->assertSame( array(), $gateway->qrcode_shown );
    }

    public function test_saving_model_97_forces_the_mod97_payment_reference(): void {
        $this->save_slip_settings( 'mod97', '%order_id%' );

        $this->assertSame( '%mod97%-%order_id%-%year%', $this->saved_slip_settings()['payment_reference'] );
    }

    public function test_saving_model_97_uses_the_order_number_when_it_is_filtered(): void {
        \add_filter( 'woocommerce_order_number', static fn( $n ) => 'WC-' . $n );

        $this->save_slip_settings( 'mod97', '%order_id%-%year%' );

        $this->assertSame( '%mod97%-%order_number%', $this->saved_slip_settings()['payment_reference'] );
    }

    public function test_saving_another_model_keeps_the_custom_payment_reference(): void {
        $this->save_slip_settings( 'auto', '%year%-%order_id%' );

        $this->assertSame( '%year%-%order_id%', $this->saved_slip_settings()['payment_reference'] );
    }

    public function test_process_payment_puts_order_on_hold_and_empties_cart(): void {
        $order = $this->create_order();

        \wc_load_cart();
        \WC()->cart->add_to_cart( $this->product() );

        $result = $this->gateway()->process_payment( $order->get_id() );

        $this->assertSame( 'success', $result['result'] );
        $this->assertSame( $this->gateway()->get_return_url( \wc_get_order( $order->get_id() ) ), $result['redirect'] );
        $this->assertSame( 'on-hold', \wc_get_order( $order->get_id() )->get_status() );
        $this->assertTrue( \WC()->cart->is_empty() );
    }

    public function test_process_payment_status_is_filterable(): void {
        \add_filter( 'wcsrb_payment_slip_payment_order_status', static fn() => 'pending' );
        \wc_load_cart();

        $order = $this->create_order( array( 'status' => 'checkout-draft' ) );

        $this->gateway()->process_payment( $order->get_id() );

        $this->assertSame( 'pending', \wc_get_order( $order->get_id() )->get_status() );
    }

    public function test_free_orders_are_completed_immediately(): void {
        \wc_load_cart();

        $order = $this->create_order( array( 'total' => 0 ) );

        $this->gateway()->process_payment( $order->get_id() );

        $this->assertTrue( \wc_get_order( $order->get_id() )->is_paid() );
    }

    public function test_regenerate_action_refreshes_payment_data(): void {
        $order = $this->create_order(
            array(
                'meta' => array(
                    '_payment_slip_data'     => array( 'purpose' => 'Legacy' ),
                    '_payment_slip_ips_data' => 'legacy',
                ),
            ),
        );

        $this->update_gateway_settings( array( 'payment_purpose' => 'Novo' ) );
        \do_action( 'woocommerce_order_action_wcsrb_gen_ips', $order );

        $order = \wc_get_order( $order->get_id() );

        $this->assertSame( 'Novo', $order->get_meta( '_wcsrb_payment_data' )['purpose'] );
        $this->assertSame( '', $order->get_meta( '_payment_slip_data' ) );
        $this->assertSame( '', $order->get_meta( '_payment_slip_ips_data' ) );
    }

    public function test_payment_data_mirrors_settings(): void {
        $this->assertSame(
            array(
                'model'     => 'auto',
                'reference' => '%order_id%-%year%',
                'purpose'   => 'Order payment',
                'code'      => 'auto',
                'account'   => Fixtures::BANK_ACCOUNT_SHORT,
            ),
            $this->gateway()->get_payment_data(),
        );
    }

    public function test_slip_is_not_shown_for_other_payment_methods(): void {
        $order = $this->create_order( array( 'payment_method' => 'bacs' ) );

        \ob_start();
        \do_action( 'woocommerce_view_order', $order->get_id() );
        $html = \ob_get_clean();

        $this->assertStringNotContainsString( 'woocommerce-order-payment-slip', $html );
        $this->assertStringNotContainsString( 'woocommerce-order-ips-qr-code', $html );
    }

    public function test_view_order_shows_slip_with_style(): void {
        $order = $this->create_order( array( 'status' => 'on-hold' ) );

        $this->update_gateway_settings( array( 'style' => 'classic' ) );

        \ob_start();
        \do_action( 'woocommerce_view_order', $order->get_id() );
        $html = \ob_get_clean();

        $this->assertStringContainsString( 'woocommerce-order-payment-slip classic', $html );
        $this->assertStringContainsString( 'woocommerce-order-ips-qr-code', $html );
    }

    public function test_email_styles_are_added_only_to_unpaid_slip_on_hold_emails(): void {
        $emails  = \WC()->mailer()->emails;
        $on_hold = $emails['WC_Email_Customer_On_Hold_Order'];

        $on_hold->object = $this->create_order( array( 'status' => 'on-hold' ) );
        $this->assertNotSame( 'base', \apply_filters( 'woocommerce_email_styles', 'base', $on_hold ) );
        $this->assertStringStartsWith( 'base', \apply_filters( 'woocommerce_email_styles', 'base', $on_hold ) );

        $on_hold->object = $this->create_order( array( 'payment_method' => 'bacs' ) );
        $this->assertSame( 'base', \apply_filters( 'woocommerce_email_styles', 'base', $on_hold ) );

        $processing         = $emails['WC_Email_Customer_Processing_Order'];
        $processing->object = $this->create_order();
        $this->assertSame( 'base', \apply_filters( 'woocommerce_email_styles', 'base', $processing ) );
    }

    public function test_slip_is_not_added_to_admin_plain_or_other_emails(): void {
        $order   = $this->create_order( array( 'status' => 'on-hold' ) );
        $on_hold = \WC()->mailer()->emails['WC_Email_Customer_On_Hold_Order'];

        $on_hold->object = $order;

        foreach ( array( array( true, false ), array( false, true ) ) as [ $sent_to_admin, $plain_text ] ) {
            \ob_start();
            \do_action( 'woocommerce_email_order_details', $order, $sent_to_admin, $plain_text, $on_hold );
            $this->assertStringNotContainsString( 'wcsrb-payment-slip', \ob_get_clean() );
        }

        $new_order         = \WC()->mailer()->emails['WC_Email_New_Order'];
        $new_order->object = $order;

        \ob_start();
        \do_action( 'woocommerce_email_order_details', $order, false, false, $new_order );
        $this->assertStringNotContainsString( 'wcsrb-payment-slip', \ob_get_clean() );
    }

    public function test_template_extender_registers_plugin_templates(): void {
        $extender = \xwp_app( 'wcsrb' )->get( Template_Extender::class );

        $this->assertSame(
            array(
                'wcsrb' => array(
                    'dir' => \WCRS_PLUGIN_PATH . 'woocommerce',
                    'key' => 'WCRS_ABSPATH',
                ),
            ),
            $extender->custom_path_tokens( array() ),
        );
        $this->assertSame(
            array(
                'checkout/payment-slip-qr-code.php' => false,
                'checkout/payment-slip.php'         => false,
            ),
            $extender->custom_template_files( array() )['wcsrb'],
        );
        $this->assertSame( \WCRS_PLUGIN_PATH . 'woocommerce/checkout/payment-slip.php', \wc_locate_template( 'checkout/payment-slip.php' ) );
    }

    /**
     * Build a fresh gateway instance with the given settings.
     *
     * @param  array<string,mixed> $settings Settings merged over the fixture defaults.
     * @return Gateway_Payment_Slip
     */
    private function make_gateway( array $settings = array() ): Gateway_Payment_Slip {
        \update_option( 'woocommerce_wcsrb_payment_slip_settings', \array_merge( Fixtures::gateway_settings(), $settings ) );

        return \xwp_app( 'wcsrb' )->make( Gateway_Payment_Slip::class );
    }

    /**
     * Build a fresh gateway instance while "on" its wp-admin settings screen.
     *
     * @return Gateway_Payment_Slip
     */
    private function make_gateway_on_settings_screen(): Gateway_Payment_Slip {
        \set_current_screen( 'woocommerce_page_wc-settings' );

        $_GET     = array(
            'page'    => 'wc-settings',
            'section' => 'wcsrb_payment_slip',
            'tab'     => 'checkout',
        );
        $_REQUEST = $_GET;

        return $this->make_gateway();
    }

    /**
     * Save the payment slip settings form the way wp-admin posts it.
     *
     * @param  string $model     Payment model.
     * @param  string $reference Payment reference.
     */
    private function save_slip_settings( string $model, string $reference ): void {
        $gateway = $this->gateway();
        $_POST   = array();

        foreach ( Fixtures::gateway_settings() as $key => $value ) {
            $_POST[ $gateway->get_field_key( $key ) ] = 'yes' === $value ? '1' : $value;
        }

        $_POST[ $gateway->get_field_key( 'payment_model' ) ]     = $model;
        $_POST[ $gateway->get_field_key( 'payment_reference' ) ] = $reference;

        $gateway->process_admin_options();
    }

    /**
     * Get the payment slip settings stored in the database.
     *
     * @return array<string,mixed>
     */
    private function saved_slip_settings(): array {
        return \get_option( $this->gateway()->get_option_key() );
    }

    /**
     * Create a site icon attachment.
     *
     * @return int
     */
    private function site_icon(): int {
        return $this->factory()->attachment->create_upload_object( \DIR_TESTDATA . '/images/a2-small.jpg' );
    }

    /**
     * Create a simple product.
     *
     * @return int
     */
    private function product(): int {
        $product = new WC_Product_Simple();
        $product->set_name( 'Proizvod' );
        $product->set_regular_price( '100' );
        $product->save();

        return $product->get_id();
    }
}
