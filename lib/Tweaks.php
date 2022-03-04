<?php

namespace Oblak\WCRS;

class Tweaks {
    public function __construct() {
        add_filter('woocommerce_currency_symbol', [$this, 'changeCurrencySymbol'], 10, 2);
    }

    /**
     * Transliterates the currency symbol to Latin script for Serbian Dinar
     *
     * @param  string $currency_symbol Currency symbol to change
     * @param  string $currency        Currency we're changing
     * @return string                  Transliterated currency symbol
     */
    public function changeCurrencySymbol($currency_symbol, $currency) {
        if (WCSRB()->getOptions()['fix_currency_symbol'] == 'no') {
            return $currency_symbol;
        }

        switch ($currency) {
            case 'RSD':
                $currency_symbol = 'RSD';
                break;
        }
        return $currency_symbol;
    }
}
