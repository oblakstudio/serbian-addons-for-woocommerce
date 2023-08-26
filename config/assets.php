<?php
/**
 * Assets configuration array
 *
 * @package Serbian Addons for WooCommerce
 * @subpackage Config
 */

defined( 'ABSPATH' ) || exit;

return array(
    'version'   => WCRS_VERSION,
    'priority'  => 50,
    'dist_path' => WCRS_PLUGIN_PATH . 'dist',
    'dist_uri'  => plugins_url( 'dist', WCRS_PLUGIN_BASENAME ),
    'assets'    => array(
        'front' => array(
            'styles'  => array( 'styles/main.css' ),
            'scripts' => array( 'scripts/main.js', 'scripts/qrcode.js' ),
        ),
        'admin' => array(
            'styles'  => array( 'styles/admin.css' ),
            'scripts' => array( 'scripts/admin.js' ),
        ),
    ),
);
