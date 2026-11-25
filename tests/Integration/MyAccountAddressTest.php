<?php
/**
 * MyAccountAddressTest class file.
 *
 * @package Serbian Addons for WooCommerce
 * @subpackage Tests
 */

namespace Oblak\WCSRB\Tests\Integration;

use Oblak\WCSRB\Tests\Support\Fixtures;
use Oblak\WCSRB\Tests\Support\TestCase;

/**
 * My Account > Addresses > Billing address.
 */
class MyAccountAddressTest extends TestCase {
    /**
     * Start a customer session (notices live there).
     */
    public function set_up(): void {
        parent::set_up();

        \wc_load_cart();
        \wc_clear_notices();
    }

    /**
     * Reset the request.
     */
    public function tear_down(): void {
        $_POST    = array();
        $_REQUEST = array();
        \wc_clear_notices();

        parent::tear_down();
    }

    public function test_saving_as_a_person_clears_the_company_numbers(): void {
        $user = $this->customer();

        $this->save_billing_address(
            $user,
            array(
                'billing_mb'   => Fixtures::MB,
                'billing_pib'  => Fixtures::PIB,
                'billing_type' => 'person',
            ),
        );

        $customer = new \WC_Customer( $user );

        $this->assertSame( 'person', $customer->get_meta( 'billing_type' ) );
        $this->assertSame( '', $customer->get_meta( 'billing_mb' ) );
        $this->assertSame( '', $customer->get_meta( 'billing_pib' ) );
    }

    public function test_companies_keep_their_numbers(): void {
        $user = $this->customer();

        $this->save_billing_address(
            $user,
            array(
                'billing_company' => 'Firma d.o.o.',
                'billing_mb'      => Fixtures::MB,
                'billing_pib'     => Fixtures::PIB,
                'billing_type'    => 'company',
            ),
        );

        $customer = new \WC_Customer( $user );

        $this->assertSame( Fixtures::MB, $customer->get_meta( 'billing_mb' ) );
        $this->assertSame( Fixtures::PIB, $customer->get_meta( 'billing_pib' ) );
    }

    /**
     * Create a customer who saved company numbers before.
     *
     * @return int
     */
    private function customer(): int {
        $user = self::factory()->user->create( array( 'role' => 'customer' ) );

        \update_user_meta( $user, 'billing_type', 'company' );
        \update_user_meta( $user, 'billing_mb', Fixtures::MB );
        \update_user_meta( $user, 'billing_pib', Fixtures::PIB );

        return $user;
    }

    /**
     * Submit the My Account billing address form.
     *
     * @param  int                  $user   Customer ID.
     * @param  array<string,string> $fields Posted fields besides the address.
     */
    private function save_billing_address( int $user, array $fields ): void {
        \wp_set_current_user( $user );

        $_POST    = \array_merge(
            array(
                'action'                         => 'edit_address',
                'billing_address_1'              => 'Bulevar oslobođenja 10',
                'billing_city'                   => 'Novi Sad',
                'billing_country'                => 'RS',
                'billing_email'                  => 'petar@example.org',
                'billing_first_name'             => 'Petar',
                'billing_last_name'              => 'Petrović',
                'billing_phone'                  => '0601234567',
                'billing_postcode'               => '21000',
                'woocommerce-edit-address-nonce' => \wp_create_nonce( 'woocommerce-edit_address' ),
            ),
            $fields,
        );
        $_REQUEST = $_POST;

        // WooCommerce redirects (and exits) after a successful save.
        \add_filter(
            'wp_redirect',
            static function () {
                throw new \RuntimeException( 'saved' );
            },
        );

        try {
            \WC_Form_Handler::save_address();
        } catch ( \RuntimeException $e ) {
            $this->assertSame( 'saved', $e->getMessage() );

            return;
        }

        $this->fail( 'The address was not saved: ' . \wp_strip_all_tags( \implode( ' ', \wp_list_pluck( \wc_get_notices( 'error' ), 'notice' ) ) ) );
    }
}
