<?php // phpcs:disable PSR1.Files.SideEffects
/**
 * Plugin Name:          Serbian Addons for WooCommerce
 * Plugin URI:           https://oblak.studio/open-source/srpski-woocommerce
 * Description:          Various addons and tweaks that make WooCommerce compatible with Serbian bureaucracy.
 * Version:              1.0.0
 * Requires PHP:         7.3
 * Author:               Oblak Studio
 * Author URI:           https://oblak.studio
 * Text Domain:          serbian-addons-for-woocommerce
 * WC requires at least: 5.7
 * WC tested up to:      6.0
*/

defined('ABSPATH') || exit;
!defined('WCRS_PLUGIN_FILE') && define('WCRS_PLUGIN_FILE', __FILE__);

require __DIR__ . '/vendor/autoload.php';

WCSRB();
