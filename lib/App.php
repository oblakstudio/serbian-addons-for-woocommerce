<?php
/**
 * Serbian_WooCommerce class file.
 *
 * @package Serbian Addons for WooCommerce
 */

namespace Oblak\WCSRB;

use Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry;
use Oblak\WCSRB\Services\Config;
use XWP\DI\Decorators\Action;
use XWP\DI\Decorators\Filter;
use XWP\DI\Decorators\Module;
use XWP\DI\Interfaces\On_Initialize;

/**
 * Main plugin class
 */
#[Module(
    container: 'wcsrb',
    hook: 'woocommerce_loaded',
    priority: 0,
    handlers: array(
        Admin\Admin_Core::class,
        Admin\Order_Edit_Page_Controller::class,
        Core\Address_Admin_Controller::class,
        Core\Address_Display_Controller::class,
        Core\Address_Field_Controller::class,
        Core\Address_Validate_Controller::class,
        Core\Block_Checkout_Data_Controller::class,
        Core\Block_Checkout_Field_Controller::class,
        Utils\Installer::class,
        Utils\Template_Extender::class,
    ),
)]
class App implements On_Initialize {
    /**
     * DI Definitions
     *
     * @return array<string,mixed>
     */
    public static function configure(): array {
        return include \WCRS_PLUGIN_PATH . 'config/definition.php';
    }

    /**
     * Constructor
     */
    public function on_initialize(): void {
        \XWP_Asset_Loader::load_bundle( include WCRS_PLUGIN_PATH . 'config/assets.php' );
    }

    /**
     * Declares compatibility with WooCommerce HPOS and the Cart & Checkout blocks
     */
    #[Action( tag: 'before_woocommerce_init', priority: 10 )]
    public function declare_feature_compatibility() {
        if ( ! \class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
            return;
        }

        foreach ( array( 'custom_order_tables', 'cart_checkout_blocks' ) as $feature ) {
            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( $feature, WCRS_PLUGIN_FILE, true );
        }
    }

    /**
     * Adds our Payment Gateway to list of WooCommerce Gateways
     *
     * @param  array<int,class-string<\WC_Payment_Gateway>|\WC_Payment_Gateway> $gateways List of gateways.
     * @param  Gateway\Gateway_Payment_Slip                                     $gw       Payment Slip Gateway.
     * @return array<int,class-string<\WC_Payment_Gateway>|\WC_Payment_Gateway>           Modified list of gateways.
     */
    #[Filter(
        tag: 'woocommerce_payment_gateways',
        priority: 50,
        invoke: Filter::INV_PROXIED,
        args: 1,
        params: array(
            Gateway\Gateway_Payment_Slip::class,
        ),
    )]
    public function add_payment_gateways( array $gateways, Gateway\Gateway_Payment_Slip $gw ) {
        $gateways[] = $gw;

        return $gateways;
    }

    /**
     * Offers the payment slip in the block checkout.
     *
     * Not injected: the registry runs on every request, and the block builds the gateway only when it is used.
     *
     * @param  PaymentMethodRegistry $registry Block payment method registry.
     */
    #[Action( tag: 'woocommerce_blocks_payment_method_type_registration', priority: 10 )]
    public function register_block_payment_method( PaymentMethodRegistry $registry ): void {
        $registry->register( new Gateway\Gateway_Payment_Slip_Block() );
    }

    /**
     * Transliterates the currency symbol to Latin script for Serbian Dinar
     *
     * @param  string $symbol   Currency symbol to change.
     * @param  string $currency Currency we're changing.
     * @param  Config $config   Configuration service.
     * @return string           Transliterated currency symbol
     */
    #[Filter( tag: 'woocommerce_currency_symbol', priority: 99, invoke: Filter::INV_PROXIED, args: 2 )]
    public function change_currency_symbol( string $symbol, string $currency, Config $config ): string {
        if ( ! $config->get( 'core', 'fix_currency_symbol' ) ) {
            return $symbol;
        }

        switch ( $currency ) {
            case 'RSD':
                $symbol = 'RSD';
                break;
        }

        return $symbol;
    }

    /**
     * Checks if we need to load the plugin JS files
     *
     * @param  bool   $load   Whether to load the script or not.
     * @param  string $script Script name.
     * @return bool           Whether to load the script or not.
     */
    #[Filter( tag: 'wcrs_can_register_script', priority: 10 )]
    public function check_asset_necessity( bool $load, string $script ) {
        return match ( $script ) {
            'main'  => ( \is_checkout() && ! \is_wc_endpoint_url() ) || \is_account_page(),
            default => $load,
        };
    }
}
