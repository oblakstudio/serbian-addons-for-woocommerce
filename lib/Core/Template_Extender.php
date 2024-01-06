<?php // phpcs:disable Squiz.Commenting.VariableComment.MissingVar
/**
 * Template_Exteneder class file.
 *
 * @package Serbian Addons for WooCommerce
 */

namespace Oblak\WooCommerce\Serbian_Addons\Core;

use Oblak\WooCommerce\Core\Base_Template_Extender;
use Oblak\WP\Decorators\Hookable;

/**
 * Adds custom templates to WooCommerce.
 *
 * @since 2.3.0
 */
#[Hookable( 'before_woocommerce_init', 99 )]
class Template_Extender extends Base_Template_Extender {
    /**
     * {@inheritDoc}
     */
    protected $base_path = WCRS_PLUGIN_PATH . 'woocommerce';

    /**
     * {@inheritDoc}
     */
    protected $path_tokens = array(
        'WCRS_ABSPATH' => WCRS_ABSPATH,
    );

    /**
     * {@inheritDoc}
     */
    protected $templates = array(
        'checkout/payment-slip-qr-code.php',
        'checkout/payment-slip.php',
    );
}
