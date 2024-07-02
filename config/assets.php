<?php
/**
 * Assets configuration array
 *
 * @package Serbian Addons for WooCommerce
 * @subpackage Config
 */

defined( 'ABSPATH' ) || exit;

return array(
    'assets'    => array(
        'admin' => array(
            'scripts' => array( 'scripts/admin.js' ),
            'styles'  => array( 'styles/admin.css' ),
        ),
        'front' => array(
            'scripts' => array( 'scripts/main.js' ),
            'styles'  => array( 'styles/main.css' ),
        ),
    ),
    'dist_path' => WCRS_PLUGIN_PATH . 'dist',
    'dist_uri'  => plugins_url( 'dist', WCRS_BASENAME ),
    'namespace' => 'wcrs',
    'priority'  => 50,
    'version'   => WCRS_VERSION,
);
