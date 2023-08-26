<?php
/**
 * Template_Exteneder class file.
 *
 * @package Serbian Addons for WooCommerce
 */

namespace Oblak\WCRS\WooCommerce;

use Oblak\WooCommerce\Core\Base_Template_Extender;

/**
 * Adds custom templates to WooCommerce.
 *
 * @since 2.3.0
 */
class Template_Extender extends Base_Template_Extender {

    /**
     * {@inheritDoc}
     *
     * @var string
     */
    protected $base_path = WCRS_PLUGIN_PATH . 'woocommerce';

    /**
     * {@inheritDoc}
     *
     * @var string[]
     */
    protected $templates = array(
        'checkout/payment-slip.php',
    );
}
