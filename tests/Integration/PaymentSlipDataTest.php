<?php
/**
 * PaymentSlipDataTest class file.
 *
 * @package Serbian Addons for WooCommerce
 * @subpackage Tests
 */

namespace Oblak\WCSRB\Tests\Integration;

use Oblak\WCSRB\Services\Payments;
use Oblak\WCSRB\Tests\Support\Fixtures;
use Oblak\WCSRB\Tests\Support\TestCase;
use WC_Order;

/**
 * Payment slip data resolution for orders with missing, partial or legacy slip meta.
 */
class PaymentSlipDataTest extends TestCase {
    public function test_order_without_slip_meta_falls_back_to_gateway_settings(): void {
        $order = $this->order_without_slip_data();
        $data  = $this->payments()->get_data( $order );

        $this->assertSame( Fixtures::BANK_ACCOUNT_SHORT, $data['account'] );
        $this->assertSame( '289', $data['code'] );
        $this->assertSame( 'Order payment', $data['purpose'] );
        $this->assertSame( $order->get_id() . '-' . $order->get_date_created()->date( 'Y' ), $data['reference'] );
        $this->assertSame( '', $data['model'] );
    }

    public function test_order_without_slip_meta_has_valid_ips_data(): void {
        $data = $this->payments()->get_data( $this->order_without_slip_data(), 'ips' );

        $this->assertSame( '160000000012345654', $data['R'] );
        $this->assertSame( '289', $data['SF'] );
    }

    public function test_partial_legacy_meta_is_completed_with_gateway_settings(): void {
        $order = $this->order_without_slip_data();
        $order->update_meta_data(
            '_payment_slip_data',
            array(
                'account' => '265-0000000123456-82',
                'purpose' => 'Legacy purpose',
            ),
        );
        $order->save();

        $data = $this->payments()->get_data( $order );

        $this->assertSame( '265-123456-82', $data['account'] );
        $this->assertSame( 'Legacy purpose', $data['purpose'] );
        $this->assertSame( '289', $data['code'] );
    }

    public function test_malformed_meta_does_not_break_formatting(): void {
        $order = $this->order_without_slip_data();
        $order->update_meta_data( '_wcsrb_payment_data', 'not-an-array' );
        $order->save();

        $this->assertSame( Fixtures::BANK_ACCOUNT_SHORT, $this->payments()->get_data( $order )['account'] );
    }

    public function test_thank_you_page_renders_slip_for_order_without_slip_meta(): void {
        $order = $this->order_without_slip_data();

        \ob_start();
        \do_action( 'woocommerce_thankyou_wcsrb_payment_slip', $order->get_id() );
        $html = \ob_get_clean();

        $this->assertStringContainsString( 'woocommerce-order-payment-slip', $html );
        $this->assertStringContainsString( Fixtures::BANK_ACCOUNT_SHORT, $html );
    }

    public function test_on_hold_email_is_sent_for_order_without_slip_meta(): void {
        $order = $this->order_without_slip_data();

        // Creating an on-hold order already sent the admin "new order" email.
        \reset_phpmailer_instance();
        \WC()->mailer()->emails['WC_Email_Customer_On_Hold_Order']->trigger( $order->get_id(), $order );

        $mail = \tests_retrieve_phpmailer_instance()->get_sent();

        $this->assertNotFalse( $mail );
        $this->assertStringContainsString( 'wcsrb-payment-slip', $mail->body );
        $this->assertStringContainsString( Fixtures::BANK_ACCOUNT_SHORT, $mail->body );
    }

    /**
     * Payment slip order without any stored slip data (e.g. switched to the gateway in wp-admin).
     *
     * @return WC_Order
     */
    private function order_without_slip_data(): WC_Order {
        $order = $this->create_order( array( 'status' => 'on-hold' ) );
        $order->delete_meta_data( '_wcsrb_payment_data' );
        $order->save();

        return \wc_get_order( $order->get_id() );
    }

    /**
     * Get the payments service.
     *
     * @return Payments
     */
    private function payments(): Payments {
        return \xwp_app( 'wcsrb' )->get( Payments::class );
    }
}
