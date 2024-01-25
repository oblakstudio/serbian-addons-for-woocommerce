<?php
/**
 * Serbian_WooCommerce class file.
 *
 * @package Serbian Addons for WooCommerce
 */

namespace Oblak\WooCommerce\Serbian_Addons;

use Oblak\WooCommerce\Core\Settings_Helper;
use Oblak\WP\Loader_Trait;
use Oblak\WP\Traits\Hook_Processor_Trait;
use Oblak\WP\Traits\Singleton as Singleton_Trait;

use function Oblak\WooCommerce\Serbian_Addons\Utils\get_ips_basedir;

/**
 * Main plugin class
 */
class Serbian_WooCommerce {
    use Hook_Processor_Trait;
    use Singleton_Trait;
    use Loader_Trait;
    use Settings_Helper {
        Settings_Helper::load_settings as load_settings_helper;
    }

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
        defined( 'WCRS_IPS_DIR' ) || define( 'WCRS_IPS_DIR', get_ips_basedir() );
        $this->init( 'woocommerce_loaded', 1 );
        $this->init_asset_loader( require WCRS_PLUGIN_PATH . 'config/assets.php', 'wcrs' );
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
     * Loads the plugin textdomain
     *
     * @hook     plugins_loaded
     * @type     action
     * @priority 1000
     */
    public function on_plugins_loaded() {
        Core\Installer::instance()->init();
    }

    /**
     * Loads the plugin settings
     *
     * @hook    woocommerce_loaded
     * @type    action
     * @priority 99
     */
    public function load_plugin_settings() {
        $this->settings = $this->load_settings( 'wcsrb', require WCRS_PLUGIN_PATH . 'config/settings.php', false );
    }

    /**
     * Declares compatibility with WooCommerce HPOS
     *
     * @hook    before_woocommerce_init
     * @type    action
     */
    public function declare_hpos_compatibility() {
        if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', WCRS_PLUGIN_FILE, true );
		}
    }

    /**
     * Adds our Payment Gateway to list of WooCommerce Gateways
     *
     * @param  string[] $gateways List of gateways.
     * @return string[]           Modified list of gateways.
     *
     * @hook     woocommerce_payment_gateways
     * @type     filter
     * @priority 50
     */
    public function add_payment_gateways( $gateways ) {
        $gateways[] = Gateway\Gateway_Payment_Slip::class;
        return $gateways;
    }

    /**
     * Transliterates the currency symbol to Latin script for Serbian Dinar
     *
     * @param  string $currency_symbol Currency symbol to change.
     * @param  string $currency        Currency we're changing.
     * @return string                  Transliterated currency symbol
     *
     * @hook     woocommerce_currency_symbol
     * @type     filter
     * @priority 99
     */
    public function change_currency_symbol( $currency_symbol, $currency ) {
        if ( ! $this->get_settings( 'general', 'fix_currency_symbol' ) ) {
            return $currency_symbol;
        }

        switch ( $currency ) {
            case 'RSD':
                $currency_symbol = 'RSD';
                break;
        }

        return $currency_symbol;
    }

    /**
     * Checks if we need to load the plugin JS files
     *
     * @param  bool   $load   Whether to load the script or not.
     * @param  string $script Script name.
     * @return bool           Whether to load the script or not.
     *
     * @hook wcrs_load_script
     * @type filter
     */
    public function check_asset_necessity( $load, $script ) {
        return match ( $script ) {
            'main' => is_checkout() && ! is_wc_endpoint_url(),
            default => $load,
        };
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
        $accounts = get_option( 'woocommerce_store_bank_accounts', array( 'acct' => array() ) )['acct'] ?? array();

        $settings['company'] = array(
            'logo'      => get_option( 'site_icon', 0 ),
            'name'      => get_option( 'woocommerce_store_name', '' ),
            'address'   => get_option( 'woocommerce_store_address', '' ),
            'address_2' => get_option( 'woocommerce_store_address_2', '' ),
            'postcode'  => get_option( 'woocommerce_store_postcode', '' ),
            'city'      => get_option( 'woocommerce_store_city', '' ),
            'country'   => wc_get_base_location()['country'],
            'accounts'  => $accounts,
        );

        return $settings;
    }
}
