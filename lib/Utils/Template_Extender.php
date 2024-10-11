<?php // phpcs:disable Squiz.Commenting
/**
 * Template_Exteneder class file.
 *
 * @package Serbian Addons for WooCommerce
 */

namespace Oblak\WCSRB\Utils;

use XWC\Template\Customizer_Base;
use XWP\DI\Decorators\Handler;

/**
 * Adds custom templates to WooCommerce.
 *
 * @since 2.3.0
 * @since 3.8.0 Moved from the `Core` namespace.
 */
#[Handler( tag: 'before_woocommerce_init', priority: 99, container: 'wcsrb' )]
class Template_Extender extends Customizer_Base {
    public function custom_path_tokens( array $tokens ): array {
        $tokens['wcsrb'] = array(
            'dir' => WCRS_PLUGIN_PATH . 'woocommerce',
            'key' => 'WCRS_ABSPATH',
        );

        return $tokens;
    }

    public function custom_template_files( array $files ): array {
        $files['wcsrb'] = array(
            'checkout/payment-slip-qr-code.php' => false,
            'checkout/payment-slip.php'         => false,
        );

        return $files;
    }
}
