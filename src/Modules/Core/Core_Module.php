<?php
/**
 * Core_Module class file.
 *
 * @package Serbian Addons for WooCommerce
 * @subpackage Core
 */

namespace Oblak\WCSRB\Core;

use XWC\Interfaces\Config_Repository;
use XWP\DI\Decorators\Filter;
use XWP\DI\Decorators\Module;

/**
 * Core plugin module
 *
 * @since 4.0.0
 */
#[Module(
    container: 'wcsrb',
    hook: 'woocommerce_loaded',
    priority: 1,
    handlers: array( Handlers\Template_Extender::class ),
)]
class Core_Module {
    /**
     * Fix currency symbol flag
     *
     * @var bool
     */
    private bool $fix_currency;

    /**
     * Constructor
     *
     * @param Config_Repository $config Config instance.
     */
    public function __construct( Config_Repository $config ) {
        $this->fix_currency = $config->get( 'core.fix_currency_symbol' );

        $config->set(
            'core.block_checkout',
            \has_block( 'woocommerce/checkout', \wc_get_page_id( 'checkout' ) ),
        );
    }

    /**
     * Transliterates the currency symbol to Latin script for Serbian Dinar
     *
     * @param  string $symbol   Currency symbol to change.
     * @param  string $currency Currency we're changing.
     * @return string
     */
    #[Filter( tag: 'woocommerce_currency_symbol', priority: 99 )]
    public function change_currency_symbol( string $symbol, string $currency ): string {
        if ( $this->fix_currency ) {
            $symbol = match ( $currency ) {
                'RSD'   => 'RSD',
                default => $symbol,
            };
        }

        return $symbol;
    }

    /**
     * Checks if we need to load the plugin JS files
     *
     * @param  bool   $load   Whether to load the script or not.
     * @param  string $script Script name.
     * @return bool           Whether to load the script or not.
     */
    #[Filter( tag: 'wcrs_can_register_script', priority: 10, context: Filter::CTX_FRONTEND )]
    public function check_asset_necessity( bool $load, string $script ) {
        return match ( $script ) {
            'main'  => ( \is_checkout() && ! \is_wc_endpoint_url() ) || \is_account_page(),
            default => $load,
        };
    }
}
