<?php
/**
 * Tweaks class file.
 *
 * @package Serbian Addons for WooCommerce
 * @subpackage WooCommerce
 */

namespace Oblak\WCRS\WooCommerce;

/**
 * WooCommerce tweaks
 */
class Tweaks {

    /**
     * Class constructor
     */
    public function __construct() {
        add_filter( 'woocommerce_currency_symbol', array( $this, 'change_currency_symbol' ), 10, 2 );
    }

    /**
     * Transliterates the currency symbol to Latin script for Serbian Dinar
     *
     * @param  string $currency_symbol Currency symbol to change.
     * @param  string $currency        Currency we're changing.
     * @return string                  Transliterated currency symbol
     */
    public function change_currency_symbol( $currency_symbol, $currency ) {
        if ( ! WCSRB()->get_settings( 'general', 'fix_currency_symbol' ) ) {
            return $currency_symbol;
        }

        switch ( $currency ) {
            case 'RSD':
                $currency_symbol = 'RSD';
                break;
        }

        return $currency_symbol;
    }
}
