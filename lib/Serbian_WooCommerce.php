<?php
/**
 * Serbian_WooCommerce class file.
 *
 * @package Serbian Addons for WooCommerce
 */

namespace Oblak\WCRS;

use Automattic\Jetpack\Constants;
use Automattic\WooCommerce\RestApi\Utilities\SingletonTrait;
use Oblak\WCRS\Core\Installer;
use Oblak\WooCommerce\Core\Settings_Helper;
use Oblak\WP\Loader_Trait;

/**
 * Main plugin class
 */
class Serbian_WooCommerce {
    use SingletonTrait;
    use Loader_Trait;
    use Settings_Helper {
        Settings_Helper::load_settings as load_settings_helper;
    }


    /**
     * Serbian WooCommerce version.
     *
     * @var string
     */
    public $version = '3.1.4';

    /**
     * Plugin options
     *
     * @var array
     */
    protected $options = array();

    /**
     * What type of request is this?
     *
     * Copied verbatim from WooCommerce
     *
     * @param  string $type admin, ajax, cron or frontend.
     * @return bool
     */
    public function is_request( $type ) {
        switch ( $type ) {
            case 'admin':
                return is_admin();
            case 'ajax':
                return defined( 'DOING_AJAX' );
            case 'cron':
                return defined( 'DOING_CRON' );
            case 'frontend':
                return ( ! is_admin() || defined( 'DOING_AJAX' ) ) && ! defined( 'DOING_CRON' ) && ! WC()->is_rest_api_request();
        }
    }

    /**
     * Private constructor
     */
    protected function __construct() {
        $this->define_constants();
        $this->init_asset_loader( require WCRS_PLUGIN_PATH . 'config/assets.php' );
        $this->load_classes();
        $this->init_hooks();
    }

    /**
     * Define plugin constants
     */
    private function define_constants() {
        $this->namespace = 'wcrs';
        Constants::is_defined( 'WCRS_ABSPATH' ) || define( 'WCRS_ABSPATH', dirname( WCRS_PLUGIN_FILE ) . '/' );
        Constants::is_defined( 'WCRS_PLUGIN_BASENAME' ) || define( 'WCRS_PLUGIN_BASENAME', plugin_basename( WCRS_PLUGIN_FILE ) );
        Constants::is_defined( 'WCRS_PLUGIN_PATH' ) || define( 'WCRS_PLUGIN_PATH', plugin_dir_path( WCRS_PLUGIN_FILE ) );
        Constants::is_defined( 'WCRS_VERSION' ) || define( 'WCRS_VERSION', $this->version );
    }

    /**
     * Loads the needed plugin classes
     */
    private function load_classes() {
        Installer::get_instance()->init();

        if ( $this->is_request( 'admin' ) ) {
            new WooCommerce\Admin\Admin_Core();
        }

        new Core\Assets();
    }

    /**
     * Plugin initialization hooks
     */
    private function init_hooks() {
        add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
        add_action( 'init', array( $this, 'init' ) );

        add_action( 'woocommerce_loaded', array( $this, 'woocommerce_loaded' ), 99 );
        add_action( 'before_woocommerce_init', array( $this, 'declare_compatibility' ), 99 );
        add_filter( 'woocommerce_init', array( $this, 'woocommerce_init' ), 99 );
        add_filter( 'woocommerce_payment_gateways', array( $this, 'add_payment_gateways' ), 50, 1 );
    }

    /**
     * Loads the plugin textdomain
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'serbian-addons-for-woocommerce',
            false,
            dirname( WCRS_PLUGIN_BASENAME ) . '/languages'
        );
    }

    /**
     * Fired on WooCommerce loaded hook
     */
    public function woocommerce_loaded() {
    }

    /**
     * Plugin initialization
     */
    public function init() {
        $this->settings = $this->load_settings( 'wcsrb', require WCRS_PLUGIN_PATH . 'config/settings.php', false );
    }

    /**
     * Declares compatibility with WooCommerce HPOS
     *
     * @since 2.3.0
     */
    public function declare_compatibility() {
        if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', WCRS_PLUGIN_FILE, true );
		}
    }

    /**
     * Fired on WooCommerce init hook
     */
    public function woocommerce_init() {
        new WooCommerce\Checkout\Field_Customizer();
        new WooCommerce\Checkout\Field_Validator();

        new WooCommerce\Order\Field_Display();

        new WooCommerce\Tweaks();
        new WooCommerce\Template_Extender();
    }

    /**
     * Adds our Payment Gateway to list of WooCommerce Gateways
     *
     * @param  string[] $gateways List of gateways.
     * @return string[]           Modified list of gateways.
     */
    public function add_payment_gateways( $gateways ) {
        $gateways[] = WooCommerce\Gateway\Payment_Slip_Gateway::class;
        return $gateways;
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
        $accounts = get_option( 'woocommerce_store_bank_accounts', array( 'acct' => array() ) );

        $settings['company'] = array(
            'logo'      => get_option( 'site_icon', 0 ),
            'name'      => get_option( 'woocommerce_store_name', '' ),
            'address'   => get_option( 'woocommerce_store_address', '' ),
            'address_2' => get_option( 'woocommerce_store_address_2', '' ),
            'postcode'  => get_option( 'woocommerce_store_postcode', '' ),
            'city'      => get_option( 'woocommerce_store_city', '' ),
            'country'   => WC()->countries->countries[ WC()->countries->get_base_country() ],
            'accounts'  => $accounts['acct'],
        );

        return $settings;
    }
}
