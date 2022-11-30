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
