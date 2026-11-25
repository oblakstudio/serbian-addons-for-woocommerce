<?php
/**
 * IpsQrEmailTest class file.
 *
 * @package Serbian Addons for WooCommerce
 * @subpackage Tests
 */

namespace Oblak\WCSRB\Tests\Integration;

use Oblak\WCSRB\Gateway\Gateway_Payment_Slip_IPS_Handler;
use Oblak\WCSRB\Services\QR_Code_Manager;
use Oblak\WCSRB\Tests\Support\TestCase;
use WC_Order;

/**
 * IPS QR code in the customer on-hold email.
 */
class IpsQrEmailTest extends TestCase {
    public function test_on_hold_email_embeds_the_qr_code(): void {
        $mail = $this->send_on_hold_email( $this->create_order() );

        $this->assertStringContainsString( 'cid:ips-qr-code', $mail->body );
        $this->assertContains( 'ips-qr-code', $this->embedded_cids() );
    }

    public function test_on_hold_email_is_sent_without_qr_code_when_the_file_is_missing(): void {
        $order = $this->create_order();

        \unlink( $this->qrc()->get_filename( $order ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink

        $mail = $this->send_on_hold_email( $order );

        $this->assertStringContainsString( 'wcsrb-payment-slip', $mail->body, 'The rest of the email is still sent' );
        $this->assertStringNotContainsString( 'cid:ips-qr-code', $mail->body );
        $this->assertNotContains( 'ips-qr-code', $this->embedded_cids() );
    }

    public function test_inline_image_hook_never_throws_for_a_missing_file(): void {
        $handler = \xwp_app( 'wcsrb' )->get( Gateway_Payment_Slip_IPS_Handler::class );
        $prop    = new \ReflectionProperty( $handler, 'filename' );
        $prop->setAccessible( true );
        $prop->setValue( $handler, \WCRS_IPS_DIR . '/does-not-exist.jpg' );

        $this->assertTrue( \wp_mail( 'customer@example.org', 'Subject', 'Body' ) );
        $this->assertNull( $prop->getValue( $handler ), 'The pending image is consumed' );
        $this->assertNotContains( 'ips-qr-code', $this->embedded_cids() );
    }

    /**
     * Send the customer on-hold email and return the captured message.
     *
     * @param  WC_Order $order Order object.
     * @return object
     */
    private function send_on_hold_email( WC_Order $order ): object {
        \reset_phpmailer_instance();
        \WC()->mailer()->emails['WC_Email_Customer_On_Hold_Order']->trigger( $order->get_id(), $order );

        $mail = \tests_retrieve_phpmailer_instance()->get_sent();

        $this->assertNotFalse( $mail, 'The email was sent' );

        return $mail;
    }

    /**
     * Content IDs of the inline images attached to the last message.
     *
     * @return array<int,string>
     */
    private function embedded_cids(): array {
        return \array_column( \tests_retrieve_phpmailer_instance()->getAttachments(), 7 );
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
