<?php
/**
 * Serbian_WooCommerce class file.
 *
 * @package Serbian Addons for WooCommerce
 */

namespace Oblak\WCSRB;

use chillerlan\QRCode\QRCode;
use XWC\Traits\Settings_API_Methods;
use XWP\DI\Decorators\Action;
use XWP\DI\Decorators\Filter;
use XWP\DI\Decorators\Module;
use XWP\DI\Interfaces\On_Initialize;
use XWP_Asset_Retriever;

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
		Utils\Installer::class,
		Utils\Template_Extender::class,
    ),
)]
class App implements On_Initialize {
    use Settings_API_Methods;
    use XWP_Asset_Retriever;

    /**
     * DI Definitions
     *
     * @return array<string,mixed>
     */
    public static function configure(): array {
        return array(
            'ips.basedir'          => \DI\factory(
                static fn() => \defined( 'WCRS_IPS_DIR' )
                    ? WCRS_IPS_DIR
                    : \wp_upload_dir()['basedir'] . '/wcrs-ips',
            ),
            'ips.generator'        => \DI\factory(
                static fn() => \class_exists( \Imagick::class )
                    ? QR\QR_Generator_ImageMagick::class
                    : QR\QR_Generator_GD::class
            ),
            QRCode::class          => \DI\factory(
                static fn( QR\QR_Code_Options $opts ) => new QRCode( $opts )
            ),
            Utils\Installer::class => \DI\factory( array( Utils\Installer::class, 'instance' ) ),
        );
    }

    /**
     * Constructor
     */
    public function on_initialize(): void {
        $this->load_bundle_config( WCRS_PLUGIN_PATH . 'config/assets.php' );
    }

    /**
     * Loads the plugin settings
     */
    #[Action( tag: 'woocommerce_loaded', priority: 99 )]
    public function load_plugin_settings() {
        try {
            $this->load_options( 'wcsrb_settings' );
        } catch ( \Exception | \Error ) {
            \wc_get_logger()->critical(
                'Failed to load plugin settings',
                array(
					'source' => 'serbian-addons-for-woocommerce',
				),
            );
            $this->settings = array();
        }

        $this->settings['core'] = \wp_parse_args(
            $this->settings['core'] ?? array(),
            array(
                'enabled_customer_types' => 'both',
                'field_ordering'         => true,
                'fix_currency_symbol'    => true,
                'remove_unneeded_fields' => false,
            ),
        );

        $this->settings['company'] = array(
            'accounts'  => \wcsrb_get_bank_accounts(),
            'address_1' => \get_option( 'woocommerce_store_address', '' ),
            'address_2' => \get_option( 'woocommerce_store_address_2', '' ),
            'city'      => \get_option( 'woocommerce_store_city', '' ),
            'country'   => \wc_get_base_location()['country'],
            'logo'      => \get_option( 'site_icon', 0 ),
            'name'      => \get_option( 'woocommerce_store_name', '' ),
            'postcode'  => \get_option( 'woocommerce_store_postcode', '' ),
        );
    }

    /**
     * Declares compatibility with WooCommerce HPOS
     */
    #[Action( tag: 'before_woocommerce_init', priority: 10 )]
    public function declare_hpos_compatibility() {
        if ( ! \class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
            return;
        }

        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
            'custom_order_tables',
            WCRS_PLUGIN_FILE,
            true,
        );
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
     * Transliterates the currency symbol to Latin script for Serbian Dinar
     *
     * @param  string $symbol   Currency symbol to change.
     * @param  string $currency Currency we're changing.
     * @return string           Transliterated currency symbol
     */
    #[Filter( tag: 'woocommerce_currency_symbol', priority: 99 )]
    public function change_currency_symbol( string $symbol, string $currency ): string {
        if ( ! $this->get_settings( 'core', 'fix_currency_symbol' ) ) {
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
