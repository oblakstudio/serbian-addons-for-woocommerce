<?php
/**
 * Helper functions
 *
 * @package Serbian Addons for WooCommerce
 * @subpackage Utils
 */

namespace Oblak\WCRS\Utils;

/**
 * Get the entity types for the customer type field
 *
 * @return string[] Entity types
 */
function get_entity_types() {

    $entity_types = array(
        'person'  => __( 'Person', 'serbian-addons-for-woocommerce' ),
        'company' => __( 'Company', 'serbian-addons-for-woocommerce' ),
    );

    /**
     * Filters the available entity types
     *
     * @param array $entity_types
     * @since 1.3.0
     */
    return apply_filters( 'woocommerce_serbian_get_entity_types', $entity_types );
}

/**
 * Checks if eFiscalization is enabled
 *
 * @return bool
 */
function is_efiscalization_enabled() {
    return WCSRB()->get_efis_opts( 'general' )['enable_efiscalization'];
}

/**
 * Formats the available tax rates for the options select
 *
 * @return string[] Tax rates
 */
function format_tax_rate_select() {
    $tax_rates_raw = WCSRB()->get_efis_client()->get_tax_rates();
    $tax_rates     = array( '' => __( 'Select tax rate', 'serbian-addons-for-woocommerce' ) );

    if ( is_wp_error( $tax_rates_raw ) ) {
        return $tax_rates;
    }

    foreach ( $tax_rates_raw['currentTaxRates']['taxCategories'] as $tax_category ) {
        foreach ( $tax_category['taxRates'] as $tax_rate ) {
            $tax_rates[ $tax_rate['label'] ] = $tax_rate['label'] . ' (' . $tax_rate['rate'] . '%)';
        }
    }

    return $tax_rates;

}
