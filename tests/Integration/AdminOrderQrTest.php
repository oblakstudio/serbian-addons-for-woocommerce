<?php
/**
 * AdminOrderQrTest class file.
 *
 * @package Serbian Addons for WooCommerce
 * @subpackage Tests
 */

namespace Oblak\WCSRB\Tests\Integration;

use Oblak\WCSRB\Admin\Order_Edit_Page_Controller;
use Oblak\WCSRB\Services\Payments;
use Oblak\WCSRB\Services\QR_Code_Manager;
use Oblak\WCSRB\Tests\Support\TestCase;
use WC_Order;

/**
 * IPS QR code on the wp-admin order screen.
 */
class AdminOrderQrTest extends TestCase {
    /**
     * Controller under test.
     *
     * @var Order_Edit_Page_Controller
     */
    private Order_Edit_Page_Controller $controller;

    /**
     * Load the admin handler.
     */
    public function set_up(): void {
        parent::set_up();

        require_once \ABSPATH . 'wp-admin/includes/template.php';

        $this->controller = $this->load_admin_handler( Order_Edit_Page_Controller::class );
    }

    /**
     * Clean up the request.
     */
    public function tear_down(): void {
        $_GET     = array();
        $_REQUEST = array();
        unset( $GLOBALS['wp_meta_boxes'], $GLOBALS['theorder'], $_SERVER['HTTP_IF_NONE_MATCH'] );
        \set_current_screen( 'front' );

        parent::tear_down();
    }

    /**
     * Throw instead of dying, so tests can inspect what the AJAX handler did.
     *
     * @param  string|int          $message wp_die() message.
     * @param  string              $title   wp_die() title.
     * @param  array<string,mixed> $args    wp_die() arguments (an integer title becomes `response`).
     *
     * @throws \WPDieException Always.
     */
    public function die_handler( $message, $title = '', $args = array() ): void {
        throw new \WPDieException( (string) $message, (int) ( $args['response'] ?? 200 ) );
    }

    public function test_ajax_view_streams_the_qr_image(): void {
        $order = $this->create_order();

        [ $output, $status ] = $this->view_qr( $order->get_id(), $this->admin() );

        $this->assertSame( 200, $status );
        $this->assertSame( $this->qrc()->read( $order, 'raw' ), $output );
    }

    public function test_ajax_view_rejects_an_invalid_nonce(): void {
        [ $output, $status ] = $this->view_qr( $this->create_order()->get_id(), $this->admin(), 'invalid' );

        $this->assertSame( '', $output );
        $this->assertSame( 403, $status );
    }

    public function test_ajax_view_requires_the_manage_woocommerce_capability(): void {
        [ $output, $status ] = $this->view_qr( $this->create_order()->get_id(), self::factory()->user->create( array( 'role' => 'customer' ) ) );

        $this->assertSame( '', $output );
        $this->assertSame( 403, $status );
    }

    public function test_ajax_view_handles_an_unknown_order(): void {
        [ $output, $status ] = $this->view_qr( 999999, $this->admin() );

        $this->assertSame( '', $output );
        $this->assertSame( 404, $status );
    }

    public function test_ajax_view_renders_a_missing_qr_code_on_demand(): void {
        $order = $this->create_order( array( 'status' => 'completed' ) );
        $this->qrc()->delete( $order );

        [ $output, $status ] = $this->view_qr( $order->get_id(), $this->admin() );

        $this->assertSame( 200, $status );
        $this->assertSame( \xwp_app( 'wcsrb' )->get( Payments::class )->get_qr_string( $order ), $this->decode_qr( $output ) );
        $this->assertFalse( $this->qrc()->has_qrcode( $order ), 'Viewing must not store the image again' );
    }

    public function test_ajax_view_ignores_orders_without_a_payment_slip(): void {
        [ $output, $status ] = $this->view_qr( $this->create_order( array( 'payment_method' => 'bacs' ) )->get_id(), $this->admin() );

        $this->assertSame( '', $output );
        $this->assertSame( 404, $status );
    }

    public function test_ajax_view_handles_a_qr_code_that_cannot_be_generated(): void {
        // A reference longer than any QR code can hold.
        \add_filter( 'woocommerce_order_number', static fn() => \str_repeat( 'X', 4000 ) );
        $this->update_gateway_settings( array( 'payment_reference' => '%order_number%' ) );

        [ $output, $status ] = $this->view_qr( $this->create_order()->get_id(), $this->admin() );

        $this->assertSame( '', $output );
        $this->assertSame( 404, $status );
    }

    public function test_ajax_view_answers_an_unchanged_qr_code_with_not_modified(): void {
        $order = $this->create_order();
        $etag  = '"' . \md5( (string) \file_get_contents( $this->qrc()->get_filename( $order ) ) ) . '"'; // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents

        [ $output, $status ] = $this->view_qr( $order->get_id(), $this->admin(), null, $etag );

        $this->assertSame( '', $output );
        $this->assertSame( 304, $status );
    }

    public function test_ajax_view_answers_an_unchanged_on_demand_qr_code_with_not_modified(): void {
        $order = $this->create_order();
        $this->qrc()->delete( $order );

        [ $image ]           = $this->view_qr( $order->get_id(), $this->admin() );
        [ $output, $status ] = $this->view_qr( $order->get_id(), $this->admin(), null, '"' . \md5( $image ) . '"' );

        $this->assertSame( '', $output );
        $this->assertSame( 304, $status );
    }

    public function test_ajax_view_lets_only_the_browser_keep_the_image(): void {
        $this->view_qr( $this->create_order()->get_id(), $this->admin() );

        // wp_die() and admin-ajax.php send their cache headers through nocache_headers().
        $this->assertSame( 'private, no-cache', \wp_get_nocache_headers()['Cache-Control'] );
    }

    public function test_ajax_view_errors_are_never_cached(): void {
        $this->view_qr( 999999, $this->admin() );

        $this->assertStringContainsString( 'no-store', \wp_get_nocache_headers()['Cache-Control'] );
    }

    public function test_ajax_view_sends_a_regenerated_qr_code_again(): void {
        $order = $this->create_order();
        $old   = (string) \file_get_contents( $this->qrc()->get_filename( $order ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents

        $this->update_gateway_settings( array( 'qrcode_color' => '#c0392b' ) );
        \do_action( 'woocommerce_order_action_wcsrb_gen_ips', $order );

        [ $output, $status ] = $this->view_qr( $order->get_id(), $this->admin(), null, '"' . \md5( $old ) . '"' );

        $this->assertSame( 200, $status );
        $this->assertNotSame( $old, $output );
        $this->assertSame( $this->qrc()->read( $order, 'raw' ), $output );
    }

    public function test_regenerate_action_is_offered_for_unpaid_slip_orders_only(): void {
        $actions = static fn( WC_Order $order ) => \apply_filters( 'woocommerce_order_actions', array(), $order );

        $this->assertSame(
            array( 'wcsrb_gen_ips' => 'Regenerate IPS QR code' ),
            $actions( $this->create_order( array( 'status' => 'on-hold' ) ) ),
        );
        $this->assertSame( array(), $actions( $this->create_order( array( 'status' => 'processing' ) ) ) );
        $this->assertSame( array(), $actions( $this->create_order( array( 'payment_method' => 'bacs' ) ) ) );
    }

    public function test_metabox_is_added_to_every_order_screen(): void {
        \do_action( 'add_meta_boxes', 'shop_order', null );

        foreach ( array_merge( \wc_get_order_types( 'order-meta-boxes' ), array( 'woocommerce_page_wc-orders' ) ) as $screen ) {
            $this->assertSame(
                array( $this->controller, 'qrcode_metabox' ),
                $GLOBALS['wp_meta_boxes'][ $screen ]['side']['default']['wcsrb-ips-qr-code']['callback'],
                "Meta box on {$screen}",
            );
        }
    }

    public function test_metabox_without_qr_code(): void {
        $order = $this->create_order( array( 'payment_method' => 'bacs' ) );

        $this->assertStringContainsString( 'No IPS QR code available for this order.', $this->metabox( $order ) );
    }

    public function test_metabox_shows_the_viewer_for_a_slip_order_without_a_saved_qr_code(): void {
        $order = $this->create_order( array( 'status' => 'completed' ) );
        $this->qrc()->delete( $order );

        $html = $this->metabox( $order );

        $this->assertStringNotContainsString( 'No IPS QR code available for this order.', $html );
        $this->assertStringContainsString( 'action=wcsrb_view_ips_qr_code', \html_entity_decode( $html ) );
    }

    public function test_metabox_with_qr_code(): void {
        $order = $this->create_order();
        $html  = $this->metabox( WCSRB_TESTS_HPOS ? $order : \get_post( $order->get_id() ) );

        \preg_match( '/<img src="([^"]+)"/', $html, $img );
        \preg_match( '/data-ips="([^"]+)"/', $html, $ips );
        \parse_str( (string) \wp_parse_url( \html_entity_decode( $img[1] ), \PHP_URL_QUERY ), $query );

        $this->assertStringStartsWith( \admin_url( 'admin-ajax.php' ), \html_entity_decode( $img[1] ) );
        $this->assertSame( 'wcsrb_view_ips_qr_code', $query['action'] );
        $this->assertSame( (string) $order->get_id(), $query['order_id'] );
        $this->assertSame( 1, \wp_verify_nonce( $query['security'], 'wcsrb-view-ips-qr-code' ) );
        $this->assertSame(
            array( 's' => \xwp_app( 'wcsrb' )->get( Payments::class )->get_qr_string( $order ) ),
            \json_decode( \html_entity_decode( $ips[1], \ENT_QUOTES ), true ),
        );
        $this->assertStringContainsString( 'Copy IPS QR string', $html );
    }

    /**
     * Call the QR code AJAX endpoint.
     *
     * @param  int    $order_id Order ID.
     * @param  int    $user_id  Current user.
     * @param  string $nonce    Nonce, generated for the user when omitted.
     * @param  string $etag     ETag the browser revalidates (If-None-Match), none when omitted.
     * @return array{string,int} Output and response code.
     */
    private function view_qr( int $order_id, int $user_id, ?string $nonce = null, ?string $etag = null ): array {
        unset( $_SERVER['HTTP_IF_NONE_MATCH'] );

        if ( null !== $etag ) {
            $_SERVER['HTTP_IF_NONE_MATCH'] = $etag;
        }

        \wp_set_current_user( $user_id );
        \add_filter( 'wp_doing_ajax', '__return_true' );
        \add_filter( 'wp_die_ajax_handler', fn() => array( $this, 'die_handler' ) );

        $_GET     = array(
            'order_id' => (string) $order_id,
            'security' => $nonce ?? \wp_create_nonce( 'wcsrb-view-ips-qr-code' ),
        );
        $_REQUEST = $_GET;

        // PHPUnit already wrote to STDOUT, so header() can only warn "headers already sent".
        \set_error_handler( static fn( $errno, $errstr ) => \str_contains( $errstr, 'headers already sent' ), \E_WARNING ); // phpcs:ignore
        \ob_start();

        try {
            \do_action( 'wp_ajax_wcsrb_view_ips_qr_code' );
            $status = 0;
        } catch ( \WPDieException $e ) {
            $status = $e->getCode();
        } finally {
            \restore_error_handler();
        }

        return array( \ob_get_clean(), $status );
    }

    /**
     * Render the meta box.
     *
     * @param  WC_Order|\WP_Post $target Meta box target.
     * @return string
     */
    private function metabox( $target ): string {
        \wp_set_current_user( $this->admin() );

        \ob_start();
        $this->controller->qrcode_metabox( $target );

        return \ob_get_clean();
    }

    /**
     * Create a shop manager.
     *
     * @return int
     */
    private function admin(): int {
        return self::factory()->user->create( array( 'role' => 'shop_manager' ) );
    }

    /**
     * Get the QR code manager.
     *
     * @return QR_Code_Manager
     */
    private function qrc(): QR_Code_Manager {
        return \xwp_app( 'wcsrb' )->get( QR_Code_Manager::class );
    }
}
