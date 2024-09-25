<?php //phpcs:disable Universal.Operators.DisallowShortTernary.Found

namespace Oblak\WCSRB\Core;

use Oblak\WP\Abstracts\Hook_Caller;
use Oblak\WP\Decorators\Filter;
use Oblak\WP\Decorators\Hookable;
use WC_Customer;
use WC_Order;

/**
 * Changes the fields on the checkout page
 *
 * @since 3.8.0
 */
#[Hookable( 'woocommerce_init', 99 )]
class Address_Display_Controller extends Hook_Caller {
    /**
     * Modifies the address format for Serbia to include necessary company information
     *
     * @param  array<string,string> $formats Address formats.
     * @return array<string,string>
     */
    #[Filter( 'woocommerce_localisation_address_formats', 'wcrs_localization_address_priority' )]
    public function modify_address_format( $formats ) {
        \add_filter( 'woocommerce_formatted_address_force_country_display', '__return_true' );

        $formats['RS'] = "{name}\n{company}\n{mb}\n{pib}\n{address_1}\n{address_2}\n{postcode} {city}, {state} {country}";

        if ( \WCSRB()->get_settings( 'core', 'remove_unneeded_fields' ) ) {
            $formats['RS'] = \str_replace( array( '{state}', '{address_2}' ), '', $formats['RS'] );
        }

        return $formats;
    }

    /**
     * Adds custom replacements to the replacements array.
     *
     * Custom fields added are:
     *  - Type
     *  - Company Number
     *  - Tax Identification Number
     *
     * @param  string[] $replacements  Replacements array.
     * @param  array    $args          Address data.
     * @return string[]                Modified replacements array
     */
    #[Filter( 'woocommerce_formatted_address_replacements', 99 )]
    public function modify_address_replacements( $replacements, $args ) {
        $replacements['{mb}']  = $args['mb'] ?? "\n";
        $replacements['{pib}'] = $args['pib'] ?? "\n";

        return $replacements;
    }

    /**
     * Modifies the address data array to include neccecary company information.
     *
     * This is used in the My Account > Addresses page.
     *
     * @param  array<string, string> $fmtd Address data array.
     * @param  int                   $uid     Customer ID.
     * @param  'billing'|'shipping'  $type    Address type (billing or shipping).
     * @return array
     */
    #[Filter( 'woocommerce_my_account_my_address_formatted_address', 99 )]
    public function modify_account_formatted_address( array $fmtd, int $uid, $type ) {
        if ( 'billing' !== $type ) {
            return $fmtd;
        }

        return \array_merge(
            $fmtd,
            $this->get_replacement_values( new WC_Customer( $uid ) ),
        );
    }

    /**
     * Modifies the address data array to include neccecary company information.
     *
     * This is used for the order addresses.
     *
     * @param  array    $address Address data array.
     * @param  WC_Order $order   Order object.
     * @return array             Modified address data array
     */
    #[Filter( 'woocommerce_order_formatted_billing_address', 99 )]
    public function modify_order_formatted_address( $address, $order ) {
        return \array_merge(
            $address,
            $this->get_replacement_values( $order ),
        );
    }

    /**
     * Billing address modifier function
     *
     * Depending on the customer(user) type we add the needed rows to the address.
     * If the customer is a company we prepend the number type before the number itself
     *
     * @param WC_Customer|WC_Order $target Customer or Order object.
     * @return array<string, string>
     */
    protected function get_replacement_values( WC_Customer|WC_Order $target ): array {
        $data = \wcsrb_get_company_data( $target );
        if ( 'company' !== $data['type'] ) {
            return array();
        }

        return array(
            'first_name' => "\n",
            'last_name'  => "\n",
            'mb'         => \sprintf(
                '%s: %s',
                \_x( 'Company Number', 'Address display', 'serbian-addons-for-woocommerce' ),
                $data['mb'] ?: "\n",
            ),
            'pib'        => \sprintf(
                '%s: %s',
                \_x( 'Tax Identification Number', 'Address display', 'serbian-addons-for-woocommerce' ),
                $data['pib'] ?: "\n",
            ),

        );
    }
}
