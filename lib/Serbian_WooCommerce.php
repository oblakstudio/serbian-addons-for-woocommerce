<?php
/**
 * Serbian_WooCommerce class file.
 *
 * @package Serbian Addons for WooCommerce
 */

namespace Oblak\WCRS;

use Automattic\Jetpack\Constants;
use Oblak\WCRS\Core\Installer;
use Oblak\WCRS\Utils\Settings_Helper;
use Oblak\WP\eFis\Client;

/**
 * Main plugin class
 */
class Serbian_WooCommerce {

    /**
     * Serbian WooCommerce version.
     *
     * @var string
     */
    public $version = '1.2.4';

    /**
     * Singleton instance
     *
     * @var Serbian_WooCommerce
     */
    protected static $instance = null;

    /**
     * Plugin options
     *
     * @var array
     */
    protected $options = array();

    /**
     * E-Fiscalization client
     *
     * @var Client
     */
    protected $efis_client = null;

    /**
     * E-Fiscalization options
     *
     * @var array
     */
    protected $efis_opts = array();

    /**
     * Disable cloning
     */
    public function __clone() {
        wc_doing_it_wrong( __FUNCTION__, 'Cloning is disabled', 'WooSync 2.1' );
    }

    /**
     * Disable unserializing
     */
    public function __wakeup() {
        wc_doing_it_wrong( __FUNCTION__, 'Unserializing is disabled', 'WooSync 2.1' );
    }

    /**
     * Retrieves the singleton instance
     *
     * @return Serbian_WooCommerce
     */
    public static function get_instance() {
        return is_null( self::$instance )
            ? self::$instance = new self()
            : self::$instance;
    }

    /**
     * Private constructor
     */
    private function __construct() {
        $this->define_constants();
        $this->load_classes();
        $this->init_hooks();
    }

    /**
     * Define plugin constants
     */
    private function define_constants() {
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

        $this->efis_opts = Settings_Helper::get_settings(
            'wcsrb_fiscalization_settings',
            WCRS_PLUGIN_PATH . 'config/defaults-fiscalization.php'
        );

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
        add_action( 'before_woocommerce_init', array( $this, 'declare_compabibility' ) );
        add_action( 'init', array( $this, 'init' ) );
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
     * Declares compatibility with various WooCommerce features
     */
    public function declare_compabibility() {
        if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
        }
    }

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
     * Plugin initialization
     */
    public function init() {
        $this->options = wp_parse_args(
            get_option( 'woocommerce_serbian' ),
            array(
                'enabled_customer_type'  => 'both',
                'remove_unneeded_fields' => 'yes',
                'fix_currency_symbol'    => 'yes',
            )
        );

        new WooCommerce\Checkout\Field_Customizer();
        new WooCommerce\Checkout\Field_Validator();

        new WooCommerce\Order\Field_Display();

        new WooCommerce\Tweaks();

        new Core\Assets();
    }

    /**
     * Get plugin options
     */
    public function get_options() {
        return $this->options;
    }

    /**
     * Get e-fiscalization options
     *
     * @param string $section Section name.
     * @return array
     */
    public function get_efis_opts( $section = null ) {
        return $section
            ? $this->efis_opts[ $section ] ?? array()
            : $this->efis_opts;
    }

    /**
     * Get e-fiscalization client
     *
     * @return Efis_Client
     */
    public function get_efis_client() {
        if ( is_null( $this->efis_client ) ) {
            $this->efis_client = new Client(
                $this->efis_opts['general']['api_username'],
                $this->efis_opts['general']['api_key'],
                $this->efis_opts['general']['language'],
            );
        }

        return $this->efis_client;
    }

}
