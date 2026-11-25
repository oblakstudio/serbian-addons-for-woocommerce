<?php
/**
 * AdminOrderValidationTest class file.
 *
 * @package Serbian Addons for WooCommerce
 * @subpackage Tests
 */

namespace Oblak\WCSRB\Tests\Integration;

use Oblak\WCSRB\Core\Address_Admin_Controller;
use Oblak\WCSRB\Services\Field_Validator;
use Oblak\WCSRB\Tests\Support\Fixtures;
use Oblak\WCSRB\Tests\Support\TestCase;
use WC_Order;

/**
 * Company number (MB) / tax number (PIB) validation when an order is edited in wp-admin.
 *
 * Drives WooCommerce's own order data meta box save routine, for both HPOS and legacy storage.
 */
class AdminOrderValidationTest extends TestCase {
    private const INVALID_MB  = '12345678';
    private const INVALID_PIB = '123456789';
    private const VALID_MB    = '20000007';
    private const VALID_PIB   = '200000005';

    /**
     * Load the admin handler and WooCommerce's order data meta box.
     */
    public function set_up(): void {
        parent::set_up();

        require_once \WC_ABSPATH . 'includes/admin/meta-boxes/class-wc-meta-box-order-data.php';

        $this->load_admin_handler( Address_Admin_Controller::class );
    }

    /**
     * Clean up the request.
     */
    public function tear_down(): void {
        $_POST = array();

        parent::tear_down();
    }

    public function test_valid_company_numbers_are_saved(): void {
        $order = $this->save_order(
            $this->create_order( array( 'type' => 'company' ) ),
            array(
                '_billing_mb'  => self::VALID_MB,
                '_billing_pib' => self::VALID_PIB,
            ),
        );

        $this->assertSame( self::VALID_MB, $order->get_meta( '_billing_mb' ) );
        $this->assertSame( self::VALID_PIB, $order->get_meta( '_billing_pib' ) );
        $this->assertFalse( $this->validator()->has_errors() );
    }

    public function test_invalid_mb_is_rejected_even_when_pib_is_valid(): void {
        $order = $this->save_order(
            $this->create_order( array( 'type' => 'company' ) ),
            array(
                '_billing_mb'  => self::INVALID_MB,
                '_billing_pib' => self::VALID_PIB,
            ),
        );

        $this->assertSame( Fixtures::MB, $order->get_meta( '_billing_mb' ), 'Invalid MB must not be saved' );
        $this->assertSame( self::VALID_PIB, $order->get_meta( '_billing_pib' ) );
        $this->assertTrue( $this->validator()->has_errors(), 'The MB error must survive the PIB validation' );
    }

    public function test_error_message_names_the_invalid_field(): void {
        $messages = \apply_filters( 'woocommerce_order_updated_messages', array() )['shop_order'];

        $this->save_order( $this->create_order( array( 'type' => 'company' ) ), array( '_billing_mb' => self::INVALID_MB ) );
        $this->assertSame( 'Company Number is invalid', $messages[ $this->redirect_message() ] );

        $this->validator()->reset();

        $this->save_order( $this->create_order( array( 'type' => 'company' ) ), array( '_billing_pib' => self::INVALID_PIB ) );
        $this->assertSame( 'Company Tax Number is invalid', $messages[ $this->redirect_message() ] );
    }

    public function test_validation_uses_the_submitted_country(): void {
        $order = $this->save_order(
            $this->create_order(
                array(
                    'country' => 'DE',
                    'type'    => 'company',
                ),
            ),
            array(
                '_billing_country' => 'RS',
                '_billing_mb'      => self::INVALID_MB,
            ),
        );

        $this->assertSame( Fixtures::MB, $order->get_meta( '_billing_mb' ), 'Switching the order to Serbia must validate the MB' );
        $this->assertTrue( $this->validator()->has_errors() );
    }

    public function test_non_serbian_orders_are_not_validated(): void {
        $order = $this->save_order(
            $this->create_order(
                array(
                    'country' => 'DE',
                    'type'    => 'company',
                ),
            ),
            array( '_billing_mb' => 'DE-123' ),
        );

        $this->assertSame( 'DE-123', $order->get_meta( '_billing_mb' ) );
        $this->assertFalse( $this->validator()->has_errors() );
    }

    public function test_legacy_order_screen_gets_the_error_message_and_redirect(): void {
        $order = $this->save_order( $this->create_order( array( 'type' => 'company' ) ), array( '_billing_mb' => self::INVALID_MB ) );

        $location = \apply_filters( 'redirect_post_location', 'post.php?post=' . $order->get_id() . '&action=edit&message=1', $order->get_id() );
        \parse_str( (string) \wp_parse_url( $location, \PHP_URL_QUERY ), $query );

        $messages = \apply_filters( 'post_updated_messages', array() );

        $this->assertSame( 'Company Number is invalid', $messages['shop_order'][ (int) $query['message'] ] );
    }

    /**
     * Save the order like the wp-admin order screen does.
     *
     * @param  WC_Order             $order  Order to save.
     * @param  array<string,string> $fields Posted billing fields, merged over the current values.
     * @return WC_Order                     Reloaded order.
     */
    private function save_order( WC_Order $order, array $fields ): WC_Order {
        $_POST = \array_merge(
            array(
                '_billing_address_1' => $order->get_billing_address_1(),
                '_billing_city'      => $order->get_billing_city(),
                '_billing_company'   => $order->get_billing_company(),
                '_billing_country'   => $order->get_billing_country(),
                '_billing_mb'        => $order->get_meta( '_billing_mb' ),
                '_billing_pib'       => $order->get_meta( '_billing_pib' ),
                '_billing_postcode'  => $order->get_billing_postcode(),
                '_billing_type'      => $order->get_meta( '_billing_type' ),
                '_payment_method'    => $order->get_payment_method(),
                'order_status'       => 'wc-' . $order->get_status(),
            ),
            $fields,
        );

        \WC_Meta_Box_Order_Data::save( $order->get_id() );

        return \wc_get_order( $order->get_id() );
    }

    /**
     * Message index the HPOS order screen redirect would carry.
     *
     * @return int
     */
    private function redirect_message(): int {
        $location = \apply_filters( 'woocommerce_redirect_order_location', 'admin.php?page=wc-orders&action=edit&id=1&message=1', 1, null );
        \parse_str( (string) \wp_parse_url( $location, \PHP_URL_QUERY ), $query );

        return (int) $query['message'];
    }

    /**
     * Get the field validator.
     *
     * @return Field_Validator
     */
    private function validator(): Field_Validator {
        return \xwp_app( 'wcsrb' )->get( Field_Validator::class );
    }
}
