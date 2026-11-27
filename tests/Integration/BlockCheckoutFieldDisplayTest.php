<?php
/**
 * BlockCheckoutFieldDisplayTest class file.
 *
 * @package Serbian Addons for WooCommerce
 * @subpackage Tests
 */

namespace Oblak\WCSRB\Tests\Integration;

use Automattic\WooCommerce\Blocks\Domain\Services\CheckoutFields;
use Automattic\WooCommerce\Blocks\Domain\Services\CheckoutFieldsAdmin;
use Automattic\WooCommerce\Blocks\Package;
use Oblak\WCSRB\Core\Block_Checkout_Field_Controller;
use Oblak\WCSRB\Tests\Support\Fixtures;
use Oblak\WCSRB\Tests\Support\TestCase;

/**
 * WooCommerce must not list the contact fields a second time: the Serbian address format, the classic admin fields
 * and the classic My Account address form already show and edit the company, MB and PIB.
 */
class BlockCheckoutFieldDisplayTest extends TestCase {
    /**
     * Register the fields again (a test may have re-registered or skipped them).
     */
    public function tear_down(): void {
        parent::tear_down();

        $this->register_fields();
        \wc_clear_notices();
    }

    public function test_order_details_and_emails_do_not_list_the_fields(): void {
        $order = $this->company_order();

        \ob_start();
        \WC()->mailer()->additional_checkout_fields( $order, false, true );
        $email = (string) \ob_get_clean();

        $checkout = Package::container()->get( CheckoutFields::class );
        $listed   = $checkout->filter_fields_for_order_confirmation(
            $checkout->get_order_additional_fields_with_values( $order, 'contact', 'other', 'view' ),
        );

        $this->assertSame( array(), \array_intersect_key( $listed, \array_flip( $this->field_ids() ) ) );
        $this->assertStringNotContainsString( Fixtures::PIB, $email );
    }

    public function test_the_block_order_confirmation_has_no_empty_additional_information(): void {
        // The block decides on its "Additional information" heading from the unfiltered fields - and WooCommerce
        // < 11 reports an unchecked toggle as false, a value. No field of ours may be counted there.
        $order = $this->company_order();

        \apply_filters( 'render_block_data', array( 'blockName' => 'woocommerce/order-confirmation-additional-fields-wrapper' ), array(), null );

        $fields = Package::container()->get( CheckoutFields::class )->get_order_additional_fields_with_values( $order, 'contact' );

        $this->assertSame( array(), \array_intersect_key( $fields, \array_flip( $this->field_ids() ) ) );
    }

    public function test_the_admin_order_screen_does_not_list_the_fields(): void {
        $order  = $this->company_order();
        $fields = Package::container()->get( CheckoutFieldsAdmin::class )->admin_contact_fields( array(), $order, 'edit' );

        $this->assertArrayHasKey( 'wcsrb/mb', $fields, 'WooCommerce would list it' );

        $fields = \apply_filters( 'woocommerce_admin_billing_fields', $fields, $order, 'edit' );

        $this->assertSame( array(), \array_intersect_key( $fields, \array_flip( $this->field_ids() ) ) );
    }

    public function test_the_account_details_form_does_not_list_the_fields(): void {
        $customer = new \WC_Customer( self::factory()->user->create( array( 'role' => 'customer' ) ) );
        $customer->set_billing_country( 'RS' );
        $customer->save();
        \wp_set_current_user( $customer->get_id() );

        // What myaccount/form-edit-account.php runs.
        \ob_start();
        \do_action( 'woocommerce_edit_account_form_start' );
        \do_action( 'woocommerce_edit_account_form_fields' );
        $form = (string) \ob_get_clean();

        $this->assertStringNotContainsString( '_wc_other/wcsrb/', $form );
    }

    public function test_saving_account_details_does_not_ask_for_the_company(): void {
        // Companies-only store: a Serbian company's name, MB and PIB are required at checkout.
        $this->set_core_setting( 'enabled_customer_types', 'company' );
        $this->register_fields();

        $customer = new \WC_Customer( self::factory()->user->create( array( 'role' => 'customer' ) ) );
        $customer->set_billing_country( 'RS' );
        $customer->save();
        \wp_set_current_user( $customer->get_id() );
        \wc_clear_notices();

        \do_action( 'woocommerce_save_account_details', $customer->get_id() );

        $this->assertSame( 0, \wc_notice_count( 'error' ), \wp_json_encode( \wc_get_notices( 'error' ) ) );
        $this->assertSame( '', \get_user_meta( $customer->get_id(), '_wc_other/wcsrb/company', true ) );
    }

    /**
     * Serbian company order placed through the block checkout.
     *
     * @return \WC_Order
     */
    private function company_order(): \WC_Order {
        $order = $this->create_order( array( 'type' => 'company' ) );
        // WooCommerce < 11 shows additional fields only for Store API orders.
        $order->set_created_via( 'store-api' );

        foreach ( array(
            'company'    => 'Firma d.o.o.',
            'is-company' => '1',
            'mb'         => Fixtures::MB,
            'pib'        => Fixtures::PIB,
        ) as $key => $value ) {
            $order->update_meta_data( "_wc_other/wcsrb/{$key}", $value );
        }
        $order->save();

        return $order;
    }

    /**
     * Contact field IDs.
     *
     * @return array<int,string>
     */
    private function field_ids(): array {
        return array( 'wcsrb/is-company', 'wcsrb/company', 'wcsrb/mb', 'wcsrb/pib' );
    }

    /**
     * Register the contact fields for the current settings.
     */
    private function register_fields(): void {
        $checkout = Package::container()->get( CheckoutFields::class );

        foreach ( $this->field_ids() as $id ) {
            $checkout->deregister_checkout_field( $id );
        }

        \xwp_app( 'wcsrb' )->get( Block_Checkout_Field_Controller::class )->register_fields();
    }
}
