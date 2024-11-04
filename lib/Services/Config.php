<?php
/**
 * Config_Service class file.
 *
 * @package Serbian Addons for WooCommerce
 * @subpackage Services
 */

namespace Oblak\WCSRB\Services;

use XWC\Traits\Settings_API_Methods;

/**
 * Configuration service
 *
 * @since 3.9.0
 */
class Config {
    use Settings_API_Methods;

    /**
     * Constructor
     */
    public function __construct() {
        try {
            $this->load_options( 'wcsrb_settings' );
        } catch ( \Exception | \Error ) {
            \wc_get_logger()->critical(
                'Failed to load plugin settings',
                array(
					'source' => 'serbian-addons-for-woocommerce',
				),
            );
            $this->settings = array();
        }

        $this->settings['core'] = \wp_parse_args(
            $this->settings['core'] ?? array(),
            array(
                'enabled_customer_types' => 'both',
                'field_ordering'         => true,
                'fix_currency_symbol'    => true,
                'remove_unneeded_fields' => false,
            ),
        );

        $this->settings['company'] = array(
            'accounts'  => $this->get_bank_accounts(),
            'address_1' => \get_option( 'woocommerce_store_address', '' ),
            'address_2' => \get_option( 'woocommerce_store_address_2', '' ),
            'city'      => \get_option( 'woocommerce_store_city', '' ),
            'country'   => \wc_get_base_location()['country'],
            'logo'      => \get_option( 'site_icon', 0 ),
            'name'      => \get_option( 'woocommerce_store_name', '' ),
            'postcode'  => \get_option( 'woocommerce_store_postcode', '' ),
        );
    }

    /**
     * Get the saved bank accounts.
     *
     * @return array<int,string>
     */
    private function get_bank_accounts(): array {
        $accounts = \get_option( 'woocommerce_store_bank_accounts', array() );

        return \xwp_str_to_arr( $accounts['acct'] ?? $accounts );
    }

    /**
     * Get a setting
     *
     * @param  string $section Main section.
     * @param  string ...$subs Subsections.
     * @return mixed
     */
    public function get( string $section = 'all', string ...$subs ): mixed {
        return $this->get_settings( $section, ...$subs );
    }
}
