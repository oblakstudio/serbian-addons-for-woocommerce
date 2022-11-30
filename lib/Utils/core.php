<?php
/**
 * Core utility functions
 *
 * @package Serbian Addons for WooCommerce
 * @subpackage Utils
 */

use Oblak\WCRS\Serbian_WooCommerce;

/**
 * Main Plugin Instance
 *
 * @return Serbian_WooCommerce
 */
function WCSRB() { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.FunctionNameInvalid
    return Serbian_WooCommerce::get_instance();
}
