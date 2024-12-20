<?php
/**
 * Serbian_WooCommerce class file.
 *
 * @package Serbian Addons for WooCommerce
 */

namespace Oblak\WCSRB;

use DI\Attribute\Inject;
use XWP\DI\Decorators\Module;
use XWP\DI\Interfaces\On_Initialize;

/**
 * Main plugin class
 */
#[Module(
    container: 'wcsrb',
    hook: 'woocommerce_loaded',
    priority: 0,
    imports:array(
        Admin\Admin_Module::class,
        Core\Core_Module::class,
        Checkout\Checkout_Module::class,
        Gateway\Gateway_Module::class,
    ),
    handlers: array(
        Core\Services\Installer::class,
    ),
)]
class App implements On_Initialize {
    /**
     * DI Definitions
     *
     * @return array<string,mixed>
     */
    public static function configure(): array {
        return include WCSRB_PATH . 'config/definition.php';
    }

    /**
     * Function to run on plugin initialization.
     *
     * @param array<string,mixed> $assets Assets configuration array.
     */
    #[Inject( array( 'config.assets' ) )]
    public function on_initialize( array $assets = array() ): void {
        \XWP_Asset_Loader::load_bundle( $assets );
    }
}
