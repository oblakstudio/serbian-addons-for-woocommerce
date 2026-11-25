<?php
/**
 * QrPipelineTest class file.
 *
 * @package Serbian Addons for WooCommerce
 * @subpackage Tests
 */

namespace Oblak\WCSRB\Tests\Integration;

use chillerlan\QRCode\Data\QRMatrix;
use chillerlan\QRCode\QRCode;
use Oblak\WCSRB\Gateway\Gateway_Payment_Slip_IPS_Handler;
use Oblak\WCSRB\QR\QR_Code_Options;
use Oblak\WCSRB\QR\QR_Generator_GD;
use Oblak\WCSRB\QR\QR_Generator_ImageMagick;
use Oblak\WCSRB\Services\QR_Code_Manager;
use Oblak\WCSRB\Tests\Support\TestCase;

/**
 * IPS QR code generation, storage and lifecycle.
 */
class QrPipelineTest extends TestCase {
    private const PAYLOAD = 'K:PR|V:01|C:1|R:160000000012345654|N:Test|I:RSD100,00|SF:289';

    /**
     * QR generators.
     *
     * @return array<string,array{class-string}>
     */
    public function provide_generators(): array {
        return array(
            'GD'          => array( QR_Generator_GD::class ),
            'ImageMagick' => array( QR_Generator_ImageMagick::class ),
        );
    }

    /**
     * @dataProvider provide_generators
     *
     * @param class-string $generator Generator class.
     */
    public function test_generator_renders_a_decodable_jpeg( string $generator ): void {
        $image = $this->render( $generator );

        $this->assertStringStartsWith( "\xFF\xD8", $image, 'JPEG magic bytes' );
        $this->assertSame( self::PAYLOAD, $this->decode( $image ) );
    }

    /**
     * @dataProvider provide_generators
     *
     * @param class-string $generator Generator class.
     */
    public function test_generator_renders_the_site_icon_logo( string $generator ): void {
        // A real IPS string: the fixed 20x20 logo space needs a QR code of realistic size.
        $payload = \xwp_app( 'wcsrb' )->get( \Oblak\WCSRB\Services\Payments::class )->get_qr_string( $this->create_order() );
        $plain   = $this->render( $generator, $payload );

        \update_option( 'site_icon', $this->site_icon() );
        $this->update_gateway_settings( array( 'qrcode_image' => 'yes' ) );

        $options = $this->options( $generator );
        $image   = ( new QRCode( $options ) )->render( $payload );

        $this->assertSame( \get_attached_file( (int) \get_option( 'site_icon' ) ), $options->logo );
        $this->assertTrue( $options->addLogoSpace );
        $this->assertNotSame( $plain, $image );
        $this->assertSame( $payload, $this->decode( $image ), 'High error correction keeps the code readable' );
    }

    public function test_colors_are_converted_for_each_generator(): void {
        $this->update_gateway_settings(
            array(
                'qrcode_color'        => '#ff0000',
                'qrcode_corner_color' => '#0000ff',
            ),
        );

        $gd      = $this->options( QR_Generator_GD::class )->moduleValues;
        $imagick = $this->options( QR_Generator_ImageMagick::class )->moduleValues;

        $this->assertSame( array( 255, 0, 0 ), $gd[ QRMatrix::M_DATA_DARK ] );
        $this->assertSame( array( 0, 0, 255 ), $gd[ QRMatrix::M_FINDER_DARK ] );
        $this->assertSame( '#ff0000', $imagick[ QRMatrix::M_DATA_DARK ] );
        $this->assertSame( '#0000ff', $imagick[ QRMatrix::M_FINDER_DARK ] );
    }

    public function test_short_hex_colors_are_expanded(): void {
        $this->update_gateway_settings( array( 'qrcode_color' => '#f00' ) );

        $this->assertSame( array( 255, 0, 0 ), $this->options( QR_Generator_GD::class )->moduleValues[ QRMatrix::M_DATA_DARK ] );
    }

    public function test_missing_logo_file_does_not_break_order_creation(): void {
        $icon = $this->site_icon();

        \update_option( 'site_icon', $icon );
        $this->update_gateway_settings( array( 'qrcode_image' => 'yes' ) );
        \unlink( \get_attached_file( $icon ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink

        $order = $this->create_order();

        $this->assertNull( $this->options( QR_Generator_GD::class )->logo );
        $this->assertTrue( $this->qrc()->has_qrcode( $order ), 'The code is generated without the logo' );
    }

    public function test_site_icon_without_file_is_ignored(): void {
        \update_option( 'site_icon', $this->factory()->post->create() );
        $this->update_gateway_settings( array( 'qrcode_image' => 'yes' ) );

        $this->assertNull( $this->options( QR_Generator_GD::class )->logo );
    }

    public function test_qr_generation_failure_never_breaks_order_creation(): void {
        // A reference longer than any QR code can hold.
        \add_filter( 'woocommerce_order_number', static fn() => \str_repeat( 'X', 4000 ) );
        $this->update_gateway_settings( array( 'payment_reference' => '%order_number%' ) );

        $order = $this->create_order();

        $this->assertGreaterThan( 0, $order->get_id() );
        $this->assertFalse( $this->qrc()->has_qrcode( $order ) );
    }

    public function test_read_formats(): void {
        $order = $this->create_order();
        $raw   = $this->qrc()->read( $order, 'raw' );

        $this->assertStringStartsWith( "\xFF\xD8", $raw );
        $this->assertSame( 'data:image/jpeg;base64,' . \base64_encode( $raw ), $this->qrc()->read( $order ) ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
    }

    public function test_read_without_code_or_with_empty_file(): void {
        $order = $this->create_order();
        $file  = $this->qrc()->get_filename( $order );

        \file_put_contents( $file, '' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
        $this->assertSame( '', $this->qrc()->read( $order ) );

        \unlink( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink
        $this->assertSame( '', $this->qrc()->read( $order ) );
        $this->assertFalse( $this->qrc()->has_qrcode( $order ) );
    }

    public function test_non_slip_orders_have_no_code(): void {
        $order = $this->create_order( array( 'payment_method' => 'bacs' ) );

        $this->assertFalse( $this->qrc()->has_qrcode( $order ) );
        $this->assertFileDoesNotExist( $this->qrc()->get_filename( $order ) );
    }

    public function test_delete(): void {
        $order = $this->create_order();

        $this->assertTrue( $this->qrc()->delete( $order ) );
        $this->assertFalse( $this->qrc()->delete( $order ), 'Nothing left to delete' );
    }

    public function test_filename(): void {
        $order = $this->create_order();
        $name  = $order->get_id() . '-' . $order->get_order_key();

        $this->assertSame( \WCRS_IPS_DIR . '/' . $name . '.jpg', $this->qrc()->get_filename( $order ) );
        $this->assertSame( $name . '.png', $this->qrc()->get_filename( $order, 'png', false ) );
        $this->assertSame( '', $this->qrc()->get_filename( null ), 'No global order' );
        $this->assertSame( '', $this->qrc()->get_filename( false ) );
    }

    public function test_template_args(): void {
        $order = $this->create_order();
        $args  = $this->qrc()->get_template_args( $order, 'order' );

        $this->assertSame( 'IPS QR Code', $args['alt'] );
        $this->assertSame( $this->qrc()->get_filename( $order ), $args['path'] );
        $this->assertStringStartsWith( 'data:image/jpeg;base64,', $args['src'] );
        $this->assertSame( 'cid:ips-qr-code', $this->qrc()->get_template_args( $order, 'email' )['src'] );
    }

    /**
     * Order lifecycle events that remove the code.
     *
     * @return array<string,array{callable}>
     */
    public function provide_cleanup_events(): array {
        return array(
            'completed' => array( static fn( $o ) => $o->update_status( 'completed' ) ),
            'trashed'   => array( static fn( $o ) => $o->delete( false ) ),
            'deleted'   => array( static fn( $o ) => $o->delete( true ) ),
        );
    }

    /**
     * @dataProvider provide_cleanup_events
     *
     * @param callable $event Event callback.
     */
    public function test_code_is_removed_when_no_longer_needed( callable $event ): void {
        $order = $this->create_order();
        $file  = $this->qrc()->get_filename( $order );

        $this->assertFileExists( $file );

        $event( $order );

        $this->assertFileDoesNotExist( $file );
    }

    public function test_regenerate_action_recreates_the_code(): void {
        $order = $this->create_order();
        $this->qrc()->delete( $order );

        \do_action( 'woocommerce_order_action_wcsrb_gen_ips', $order );

        $this->assertTrue( $this->qrc()->has_qrcode( $order ) );
    }

    public function test_thank_you_page_shows_the_code_only_where_configured(): void {
        $order = $this->create_order();

        $this->assertStringContainsString( 'data:image/jpeg;base64,', $this->thank_you( $order->get_id() ) );

        $this->update_gateway_settings( array( 'qrcode_shown' => array( 'email' ) ) );

        $this->assertStringNotContainsString( 'woocommerce-order-ips-qr-code', $this->thank_you( $order->get_id() ) );
    }

    public function test_inline_image_rejected_by_phpmailer_never_throws(): void {
        $handler = \xwp_app( 'wcsrb' )->get( Gateway_Payment_Slip_IPS_Handler::class );
        $prop    = new \ReflectionProperty( $handler, 'filename' );
        $prop->setAccessible( true );
        // Readable, but PHPMailer refuses stream wrapper paths.
        $prop->setValue( $handler, 'file://' . \DIR_TESTDATA . '/images/a2-small.jpg' );

        $this->assertTrue( \wp_mail( 'customer@example.org', 'Subject', 'Body' ) );
        $this->assertSame( array(), \tests_retrieve_phpmailer_instance()->getAttachments() );
    }

    public function test_container_definitions(): void {
        $app = \xwp_app( 'wcsrb' );

        $this->assertSame( \WCRS_IPS_DIR, $app->get( 'ips.basedir' ) );
        $this->assertSame( \class_exists( \Imagick::class ) ? QR_Generator_ImageMagick::class : QR_Generator_GD::class, $app->get( 'ips.generator' ) );
        $this->assertInstanceOf( QRCode::class, $app->make( QRCode::class ) );
        $this->assertInstanceOf( \XWP_Asset_Bundle::class, $app->get( \XWP_Asset_Bundle::class ) );
        $this->assertInstanceOf( \Oblak\WCSRB\Utils\Installer::class, $app->get( \Oblak\WCSRB\Utils\Installer::class ) );
    }

    /**
     * Build QR options for a generator from the current gateway settings.
     *
     * @param  class-string $generator Generator class.
     * @return QR_Code_Options
     */
    private function options( string $generator ): QR_Code_Options {
        return \xwp_app( 'wcsrb' )->make( QR_Code_Options::class, array( 'generator' => $generator ) );
    }

    /**
     * Render the test payload.
     *
     * @param  class-string $generator Generator class.
     * @return string
     */
    private function render( string $generator, string $payload = self::PAYLOAD ): string {
        if ( QR_Generator_ImageMagick::class === $generator && ! \extension_loaded( 'imagick' ) ) {
            $this->markTestSkipped( 'The imagick extension is not available.' );
        }

        return ( new QRCode( $this->options( $generator ) ) )->render( $payload );
    }

    /**
     * Decode a QR image.
     *
     * @param  string $image Image blob.
     * @return string
     */
    private function decode( string $image ): string {
        return $this->decode_qr( $image );
    }

    /**
     * Render the payment slip thank you page.
     *
     * @param  int $order_id Order ID.
     * @return string
     */
    private function thank_you( int $order_id ): string {
        \ob_start();
        \do_action( 'woocommerce_thankyou_wcsrb_payment_slip', $order_id );

        return \ob_get_clean();
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
     * Get the QR code manager.
     *
     * @return QR_Code_Manager
     */
    private function qrc(): QR_Code_Manager {
        return \xwp_app( 'wcsrb' )->get( QR_Code_Manager::class );
    }
}
