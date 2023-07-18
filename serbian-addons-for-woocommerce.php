<?php
/**
 * Plugin Name:          Serbian Addons for WooCommerce
 * Plugin URI:           https://oblak.studio/open-source/srpski-woocommerce
 * Description:          Various addons and tweaks that make WooCommerce compatible with Serbian bureaucracy.
 * Version:              2.2.0
 * Requires PHP:         7.3
 * Author:               Oblak Studio
 * Author URI:           https://oblak.studio
 * Tested up to:         6.1
 * WC requires at least: 5.7
 * WC tested up to:      7.1
 * License:              GPLv2 or later
 * License URI:          https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:          serbian-addons-for-woocommerce
 *
 * @package Serbian Addons for WooCommerce
 */

defined( 'ABSPATH' ) || exit;

defined( 'WCRS_PLUGIN_FILE' ) || define( 'WCRS_PLUGIN_FILE', __FILE__ );

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/lib/Utils/core.php';
require __DIR__ . '/lib/Utils/helpers.php';

add_action( 'before_woocommerce_init', function() {
	if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
	}
} );

add_action(
    'woocommerce_loaded',
    function() {
        WCSRB();
    }
);
