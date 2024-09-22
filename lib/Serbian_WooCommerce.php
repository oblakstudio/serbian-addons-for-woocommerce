<?php
/**
 * Serbian_WooCommerce class file.
 *
 * @package Serbian Addons for WooCommerce
 */

namespace Oblak\WooCommerce\Serbian_Addons;

use Oblak\WooCommerce\Core\Settings_Helper;
use Oblak\WP\Decorators\Action;
use Oblak\WP\Decorators\Filter;
use Oblak\WP\Traits\Hook_Processor_Trait;
use Oblak\WP\Traits\Singleton;

/**
 * Main plugin class
 */
class Serbian_WooCommerce {
    use Hook_Processor_Trait;
    use Singleton;
    use Settings_Helper {
        Settings_Helper::load_settings as load_settings_helper;
    }
    use \XWP_Asset_Retriever;

    /**
     * Serbian WooCommerce version.
     *
     * @var string
     */
    public string $version = WCRS_VERSION;

    /**
     * Private constructor
     */
    protected function __construct() {
        \defined( 'WCRS_IPS_DIR' ) || \define( 'WCRS_IPS_DIR', \wcsrb_get_ips_basedir() );
        $this->init( 'woocommerce_loaded', 1 );
        $this->load_bundle_config( WCRS_PLUGIN_PATH . 'config/assets.php' );
    }

    /**
     * {@inheritDoc}
     */
    protected function get_dependencies(): array {
        return array(
            Admin\Admin_Core::class,
			Core\Template_Extender::class,
            Checkout\Field_Customizer::class,
            Checkout\Field_Validator::class,
            Order\Field_Display::class,
        );
    }

    /**
     * Runs the registered hooks for the plugin.
     */
    public function run_hooks() {
        \xwp_invoke_hooked_methods( $this );
    }

    /**
     * Get the settings array from the database
     *
     * We use the helper settings loader to load the settings, and then we add the company info
     * because it is a mix of our settings and WooCommerce settings.
     *
     * @param  string $prefix        The settings prefix.
     * @param  array  $raw_settings  The settings fields.
     * @param  mixed  $default_value The default value for the settings.
     * @return array                 The settings array.
     */
    protected function load_settings( string $prefix, array $raw_settings, $default_value ): array {
        $settings = $this->load_settings_helper( $prefix, $raw_settings, $default_value );

        $settings['company'] = array(
            'accounts'  => \wcsrb_get_bank_accounts(),
            'address'   => \get_option( 'woocommerce_store_address', '' ),
            'address_2' => \get_option( 'woocommerce_store_address_2', '' ),
            'city'      => \get_option( 'woocommerce_store_city', '' ),
            'country'   => \wc_get_base_location()['country'],
            'logo'      => \get_option( 'site_icon', 0 ),
            'name'      => \get_option( 'woocommerce_store_name', '' ),
            'postcode'  => \get_option( 'woocommerce_store_postcode', '' ),
        );

        return $settings;
    }

    /**
     * Initializes the installer
     */
    #[Action( tag: 'plugins_loaded', priority: 1000 )]
    public function on_plugins_loaded() {
        Core\Installer::instance()->init();
    }

    /**
     * Loads the plugin settings
     */
    #[Action( tag: 'woocommerce_loaded', priority: 99 )]
    public function load_plugin_settings() {
        $this->settings = $this->load_settings(
            'wcsrb',
            require WCRS_PLUGIN_PATH . 'config/settings.php',
            false,
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
     * @param  string[] $gateways List of gateways.
     * @return string[]           Modified list of gateways.
     */
    #[Filter( tag: 'woocommerce_payment_gateways', priority: 50 )]
    public function add_payment_gateways( $gateways ) {
        $gateways[] = Gateway\Gateway_Payment_Slip::class;
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
        if ( ! $this->get_settings( 'general', 'fix_currency_symbol' ) ) {
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
