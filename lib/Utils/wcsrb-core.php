<?php // phpcs:disable WordPress.NamingConventions.ValidFunctionName.FunctionNameInvalid
/**
 * Core utility functions
 *
 * @package Serbian Addons for WooCommerce
 * @subpackage Utils
 */

use Oblak\WooCommerce\Serbian_Addons\Serbian_WooCommerce;

/**
 * Main Plugin Instance
 *
 * @return Serbian_WooCommerce
 */
function WCSRB() {
    return Serbian_WooCommerce::instance();
}
