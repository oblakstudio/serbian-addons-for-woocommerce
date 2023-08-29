<?php
/**
 * Assets class file.
 *
 * @package Serbian Addons for WooCommerce
 * @subpackage Core
 */

namespace Oblak\WCRS\Core;

use Oblak\WP\Asset_Loader;

/**
 * Handles asset management
 */
class Assets {

    /**
     * Class constructor
     */
    public function __construct() {
        add_filter( 'wcrs_load_script', array( $this, 'check_necessity' ), 99, 2 );
    }

    /**
     * Checks if we need to load the plugin JS files
     *
     * @param  bool   $load   Whether to load the script or not.
     * @param  string $script Script name.
     * @return bool           Whether to load the script or not.
     */
    public function check_necessity( $load, $script ) {
        switch ( $script ) {
            case 'main':
                return is_checkout() && ! is_wc_endpoint_url();
            case 'qrcode':
                global $wp;
                $order = wc_get_order( $wp->query_vars['order-received'] ?? 0 );
                return is_checkout() &&
                    is_wc_endpoint_url( 'order-received' ) &&
                    $order &&
                    'wcsrb_payment_slip' === $order->get_payment_method();
            default:
                return $load;
        }
    }
}
