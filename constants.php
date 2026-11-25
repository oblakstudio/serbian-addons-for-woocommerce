<?php
/**
 * PHPStan constants file.
 *
 * Declares the constants the main plugin file defines at runtime, so static analysis knows them.
 *
 * @package Serbian Addons for WooCommerce
 * @subpackage Dev
 */

define( 'WCRS_PLUGIN_FILE', __FILE__ );
define( 'WCRS_ABSPATH', dirname( WCRS_PLUGIN_FILE ) . '/' );
define( 'WCRS_PLUGIN_BASE', 'serbian-addons-for-woocommerce/serbian-addons-for-woocommerce.php' );
define( 'WCRS_PLUGIN_PATH', WCRS_ABSPATH );
define( 'WCRS_VERSION', '0.0.0' );
define( 'WCRS_IPS_DIR', WCRS_ABSPATH . 'wcrs-ips' );
