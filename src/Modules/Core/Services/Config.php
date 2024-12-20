<?php
/**
 * Config_Service class file.
 *
 * @package Serbian Addons for WooCommerce
 * @subpackage Services
 */

namespace Oblak\WCSRB\Core\Services;

use XWC_Config;

/**
 * Configuration service
 *
 * @since 3.9.0
 */
class Config extends XWC_Config {
    /**
     * Company settings
     *
     * @var array<string,mixed>
     */
    private array $company;

    /**
     * Get the company settings
     *
     * @return array<string,mixed>
     */
    private function get_company(): array {
        return $this->company ??= array(
            'accounts'  => \wcsrb_get_bank_accounts(),
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
     * Load the company settings
     *
     * @return static
     */
    private function load_company(): static {
        $this->settings['company'] = $this->get_company();

        return $this;
    }

    /**
     * Reload the settings
     *
     * @return static
     */
    public function reload(): static {
        return parent::reload()->load_company();
    }
}
