<?php
/**
 * StoreApiCheckoutTest class file.
 *
 * @package Serbian Addons for WooCommerce
 * @subpackage Tests
 */

namespace Oblak\WCSRB\Tests\Integration;

use Automattic\WooCommerce\Blocks\Domain\Services\CheckoutFields;
use Automattic\WooCommerce\Blocks\Package;
use Oblak\WCSRB\Services\Payments;
use Oblak\WCSRB\Services\QR_Code_Manager;
use Oblak\WCSRB\Tests\Support\Fixtures;
use Oblak\WCSRB\Tests\Support\TestCase;
use WC_Product_Simple;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Block checkout orders, placed through the Store API.
 *
 * Separate processes: the Store API defines WOOCOMMERCE_CHECKOUT, which would make is_checkout()
 * true for every test that runs after it.
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class StoreApiCheckoutTest extends TestCase {
    /**
     * Act like a Store API request and load a cart with one virtual product (no shipping needed).
     */
    public function set_up(): void {
        parent::set_up();

        // What WC()->is_store_api_request() looks at; the locale is cached per request, so build it again.
        $_SERVER['REQUEST_URI']  = '/wp-json/wc/store/v1/checkout';
        \WC()->countries->locale = array();

        \add_filter( 'woocommerce_store_api_disable_nonce_check', '__return_true' );

        $product = new WC_Product_Simple();
        $product->set_name( 'Uplatnica test' );
        $product->set_regular_price( '1000' );
        $product->set_virtual( true );
        $product->save();

        \wc_load_cart();
        \WC()->cart->empty_cart();
        \WC()->cart->add_to_cart( $product->get_id() );
    }

    /**
     * Empty the cart.
     */
    public function tear_down(): void {
        \WC()->cart->empty_cart();
        \remove_filter( 'woocommerce_store_api_disable_nonce_check', '__return_true' );

        parent::tear_down();
    }

    /**
     * Billing addresses.
     *
     * @return array<string,array{array<string,string>}>
     */
    public function provide_billing_addresses(): array {
        $customer = array(
            'email'      => 'petar@example.com',
            'first_name' => 'Petar',
            'last_name'  => 'Petrovic',
        );

        return array(
            'serbian address' => array(
                $customer + array(
                    'address_1' => 'Knez Mihailova 1',
                    'city'      => 'Beograd',
                    'country'   => 'RS',
                    'phone'     => '0601234567',
                    'postcode'  => '11000',
                ),
            ),
            'foreign address' => array(
                $customer + array(
                    'address_1' => 'Unter den Linden 1',
                    'city'      => 'Berlin',
                    'country'   => 'DE',
                    'phone'     => '+49301234567',
                    'postcode'  => '10115',
                ),
            ),
            // No WooCommerce locale for Croatia: it falls back to the default locale.
            'croatian address' => array(
                $customer + array(
                    'address_1' => 'Ilica 1',
                    'city'      => 'Zagreb',
                    'country'   => 'HR',
                    'phone'     => '+38511234567',
                    'postcode'  => '10000',
                    'state'     => 'HR-21',
                ),
            ),
        );
    }

    /**
     * Checkout paying by payment slip.
     *
     * @dataProvider provide_billing_addresses
     *
     * @param array<string,string> $address Billing address.
     */
    public function test_checkout_paying_by_payment_slip( array $address ): void {
        $response = $this->checkout(
            array(
                'billing_address' => $address,
                'payment_method'  => 'wcsrb_payment_slip',
            ),
        );
        $data     = $response->get_data();

        $this->assertSame( 200, $response->get_status(), \wp_json_encode( $data ) );
        $this->assertSame( 'success', $data['payment_result']['payment_status'] );

        $order = \wc_get_order( $data['order_id'] );

        $this->assertSame( 'wcsrb_payment_slip', $order->get_payment_method() );
        $this->assertSame( 'on-hold', $order->get_status() );
        $this->assertSame( Fixtures::BANK_ACCOUNT_SHORT, $order->get_meta( '_wcsrb_payment_data' )['account'] ?? null );
        $this->assertFileExists( \xwp_app( 'wcsrb' )->get( QR_Code_Manager::class )->get_filename( $order ) );
    }

    public function test_serbian_company_checkout(): void {
        $response = $this->checkout( $this->serbian_company() );

        $this->assertSame( 200, $response->get_status(), \wp_json_encode( $response->get_data() ) );
    }

    public function test_a_serbian_company_must_enter_its_name_and_numbers(): void {
        $response = $this->checkout( $this->serbian_company( array( 'wcsrb/is-company' => true ) ) );
        $errors   = \wp_json_encode( $response->get_data() );

        $this->assertSame( 400, $response->get_status() );

        foreach ( array( 'Company name is required', 'Company Number is required', 'Tax Number is required' ) as $message ) {
            $this->assertStringContainsString( $message, $errors );
        }
    }

    public function test_an_invalid_company_number_is_rejected(): void {
        $body                                  = $this->serbian_company();
        $body['additional_fields']['wcsrb/mb'] = '12345678';

        $response = $this->checkout( $body );

        $this->assertSame( 400, $response->get_status() );
        $this->assertStringContainsString( 'Company number is invalid', \wp_json_encode( $response->get_data() ) );
    }

    public function test_a_serbian_company_is_stored_like_the_classic_checkout(): void {
        $order = $this->place_order( $this->serbian_company() );

        $this->assertSame( 'company', $order->get_meta( '_billing_type' ) );
        $this->assertSame( 'Firma d.o.o.', $order->get_billing_company() );
        $this->assertSame( Fixtures::MB, $order->get_meta( '_billing_mb' ) );
        $this->assertSame( Fixtures::PIB, $order->get_meta( '_billing_pib' ) );
        $this->assertSame( 'Firma d.o.o.', $order->get_shipping_company(), 'For couriers and labels' );
        $this->assertStringContainsString( '|SF:221|', \xwp_app( 'wcsrb' )->get( Payments::class )->get_qr_string( $order ), 'Company payment code' );
    }

    public function test_the_order_keeps_no_woocommerce_copy_of_the_contact_fields(): void {
        // The classic keys are the only copy (an unticked toggle would be stored as '0').
        $order = \wc_get_order( $this->place_order( $this->serbian_company() )->get_id() );

        foreach ( array( 'is-company', 'company', 'mb', 'pib' ) as $key ) {
            $this->assertSame( '', $order->get_meta( "_wc_other/wcsrb/{$key}" ), $key );
        }
    }

    public function test_a_serbian_person_keeps_no_company_data(): void {
        $order = $this->place_order(
            $this->serbian_company(
                array(
                    'wcsrb/company'    => 'Stara firma',
                    'wcsrb/is-company' => false,
                    'wcsrb/mb'         => Fixtures::MB,
                    'wcsrb/pib'        => Fixtures::PIB,
                ),
            ),
        );

        $this->assertSame( 'person', $order->get_meta( '_billing_type' ) );
        $this->assertSame( '', $order->get_billing_company() );
        $this->assertSame( '', $order->get_meta( '_billing_mb' ) );
        $this->assertSame( '', $order->get_meta( '_billing_pib' ) );
        $this->assertSame( '', $order->get_shipping_company() );
    }

    public function test_a_foreign_buyer_keeps_the_company_name(): void {
        $order = $this->place_order(
            array(
                'additional_fields' => array( 'wcsrb/company' => 'Muster GmbH' ),
                'billing_address'   => $this->provide_billing_addresses()['foreign address'][0],
                'payment_method'    => 'wcsrb_payment_slip',
            ),
        );

        $this->assertSame( 'person', $order->get_meta( '_billing_type' ) );
        $this->assertSame( 'Muster GmbH', $order->get_billing_company() );
        $this->assertSame( '', $order->get_shipping_company(), 'Only company orders' );
    }

    public function test_a_customer_keeps_the_company_data_for_the_next_checkout(): void {
        $user = self::factory()->user->create( array( 'role' => 'customer' ) );
        \wp_set_current_user( $user );
        \WC()->customer = new \WC_Customer( $user, true );

        $this->place_order( $this->serbian_company() );

        $this->assertSame( 'company', \get_user_meta( $user, 'billing_type', true ) );
        $this->assertSame( 'Firma d.o.o.', \get_user_meta( $user, 'billing_company', true ) );
        $this->assertSame( Fixtures::MB, \get_user_meta( $user, 'billing_mb', true ) );
        $this->assertSame( Fixtures::PIB, \get_user_meta( $user, 'billing_pib', true ) );
        $this->assertSame( '', \get_user_meta( $user, '_wc_other/wcsrb/mb', true ), 'No second copy to go stale' );
    }

    public function test_a_customer_buying_as_a_person_loses_the_saved_company_data(): void {
        $customer = new \WC_Customer( self::factory()->user->create( array( 'role' => 'customer' ) ) );
        $customer->set_billing_company( 'Firma d.o.o.' );
        $customer->update_meta_data( 'billing_type', 'company' );
        $customer->update_meta_data( 'billing_mb', Fixtures::MB );
        $customer->update_meta_data( 'billing_pib', Fixtures::PIB );
        $customer->save();
        \wp_set_current_user( $customer->get_id() );
        \WC()->customer = new \WC_Customer( $customer->get_id(), true );

        $this->place_order( $this->serbian_company( array( 'wcsrb/is-company' => false ) ) );

        $this->assertSame( 'person', \get_user_meta( $customer->get_id(), 'billing_type', true ) );
        $this->assertSame( '', \get_user_meta( $customer->get_id(), 'billing_company', true ) );
        $this->assertSame( '', \get_user_meta( $customer->get_id(), 'billing_mb', true ) );
        $this->assertSame( '', \get_user_meta( $customer->get_id(), 'billing_pib', true ) );
    }

    /**
     * Place an order and return it.
     *
     * @param  array<string,mixed> $body Request body.
     * @return \WC_Order
     */
    private function place_order( array $body ): \WC_Order {
        $response = $this->checkout( $body );

        $this->assertSame( 200, $response->get_status(), \wp_json_encode( $response->get_data() ) );

        return \wc_get_order( $response->get_data()['order_id'] );
    }

    /**
     * Checkout request of a Serbian company paying by payment slip.
     *
     * @param  array<string,mixed>|null $fields Contact fields. Default a valid company.
     * @return array<string,mixed>
     */
    private function serbian_company( ?array $fields = null ): array {
        return array(
            'additional_fields' => $fields ?? array(
                'wcsrb/company'    => 'Firma d.o.o.',
                'wcsrb/is-company' => true,
                'wcsrb/mb'         => Fixtures::MB,
                'wcsrb/pib'        => Fixtures::PIB,
            ),
            'billing_address'   => $this->provide_billing_addresses()['serbian address'][0],
            'payment_method'    => 'wcsrb_payment_slip',
        );
    }

    /**
     * Place the order in the cart through the Store API checkout route.
     *
     * @param  array<string,mixed> $body Request body.
     * @return WP_REST_Response
     */
    private function checkout( array $body ): WP_REST_Response {
        $request = new WP_REST_Request( 'POST', '/wc/store/v1/checkout' );
        $request->set_header( 'Content-Type', 'application/json' );
        $request->set_body( (string) \wp_json_encode( $body ) );

        return \rest_get_server()->dispatch( $request );
    }
}
