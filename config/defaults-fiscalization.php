<?php
/**
 * Default options for eFiscalization
 *
 * @package Serbian Addons for WooCommerce
 * @subpackage Config
 */

defined( 'ABSPATH' ) || exit;

return array(
    'general' => array(
        'enable_efiscalization' => false,
        'api_username'          => '',
        'api_key'               => '',
        'language'              => 'sr-Cyrl-RS',
    ),
    'taxes'   => array(
        'tax_rate' => '',
    ),
);
