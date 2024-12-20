<?php
/**
 * Plugin Name:          Serbian Addons for WooCommerce
 * Plugin URI:           https://oblak.studio/open-source/srpski-woocommerce
 * Description:          Various addons and tweaks that make WooCommerce compatible with Serbian bureaucracy.
 * Version:              0.0.0
 * Requires PHP:         8.0
 * Author:               Oblak Studio
 * Author URI:           https://oblak.studio
 * Tested up to:         6.6
 * WC requires at least: 8.5
 * WC tested up to:      9.3
 * License:              GPLv2 or later
 * License URI:          https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:          serbian-addons-for-woocommerce
 * Domain Path:          /languages
 * Requires Plugins:     woocommerce
 *
 * @package Serbian Addons for WooCommerce
 */

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.WhiteSpace.OperatorSpacing.SpacingBefore
defined( 'WCSRB_FILE' ) || define( 'WCSRB_FILE', __FILE__ );
defined( 'WCSRB_ABS' )  || define( 'WCSRB_ABS', dirname( WCSRB_FILE ) . '/' );
defined( 'WCSRB_BASE' ) || define( 'WCSRB_BASE', plugin_basename( WCSRB_FILE ) );
defined( 'WCSRB_PATH' ) || define( 'WCSRB_PATH', plugin_dir_path( WCSRB_FILE ) );
defined( 'WCSRB_VER' )  || define( 'WCSRB_VER', '0.0.0' );
// phpcs:enable WordPress.WhiteSpace.OperatorSpacing.SpacingBefore

require __DIR__ . '/vendor/autoload_packages.php';

xwp_load_app(
    app: array(
        'compile'     => false,
        'compile_dir' => __DIR__ . '/cache',
        'id'          => 'wcsrb',
        'module'      => \Oblak\WCSRB\App::class,
    ),
    hook: 'woocommerce_loaded',
    priority: -1,
);
