<?php
/**
 * CheckoutValidationTest class file.
 *
 * @package Serbian Addons for WooCommerce
 * @subpackage Tests
 */

namespace Oblak\WCSRB\Tests\Integration;

use Oblak\WCSRB\Tests\Support\Fixtures;
use Oblak\WCSRB\Tests\Support\TestCase;
use WP_Error;

/**
 * Company number (MB) / tax number (PIB) validation at checkout.
 */
class CheckoutValidationTest extends TestCase {
    public function test_valid_company_passes(): void {
        $this->assertSame( array(), $this->validate( array() )->get_error_codes() );
    }

    public function test_invalid_numbers_are_reported_once_each(): void {
        $errors = $this->validate(
            array(
                'billing_mb'  => '12345678',
                'billing_pib' => '123456789',
            ),
        );

        $this->assertSame( array( 'billing_mb_validation', 'billing_pib_validation' ), $errors->get_error_codes() );
        $this->assertSame( array( 'id' => 'billing_mb' ), $errors->get_error_data( 'billing_mb_validation' ) );
    }

    public function test_missing_company_name_is_required(): void {
        $this->assertSame( array( 'billing_company_required' ), $this->validate( array( 'billing_company' => '' ) )->get_error_codes() );
    }

    public function test_persons_and_foreign_companies_are_not_validated(): void {
        $this->assertFalse( $this->validate( array( 'billing_type' => 'person', 'billing_mb' => 'x' ) )->has_errors() ); // phpcs:ignore
        $this->assertFalse( $this->validate( array( 'billing_country' => 'DE', 'billing_mb' => 'x' ) )->has_errors() ); // phpcs:ignore
    }

    /**
     * Run the checkout validation for a company customer.
     *
     * @param  array<string,string> $fields Posted fields merged over a valid Serbian company.
     * @return WP_Error
     */
    private function validate( array $fields ): WP_Error {
        $errors = new WP_Error();

        \do_action(
            'woocommerce_after_checkout_validation',
            \array_merge(
                array(
                    'billing_company' => 'Firma d.o.o.',
                    'billing_country' => 'RS',
                    'billing_mb'      => Fixtures::MB,
                    'billing_pib'     => Fixtures::PIB,
                    'billing_type'    => 'company',
                ),
                $fields,
            ),
            $errors,
        );

        return $errors;
    }
}
