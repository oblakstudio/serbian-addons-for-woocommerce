<?php
/**
 * Assets configuration array
 *
 * @package Serbian Addons for WooCommerce
 * @subpackage Config
 */

defined( 'ABSPATH' ) || exit;

return array(
    'assets'   => array(
        'admin' => array(
            'css/admin/admin.css',
            'js/admin/admin.js',
        ),
        'front' => array(
            'css/front/main.css',
            'js/front/main.js',
        ),
    ),
    'base_dir' => WCRS_PLUGIN_PATH . 'dist',
    'base_uri' => plugins_url( 'dist', WCRS_PLUGIN_BASE ),
    'id'       => 'wcrs',
    'manifest' => 'assets.php',
    'priority' => 500,
    'version'  => WCRS_VERSION,
);
