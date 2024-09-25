<?php
/**
 * Serbian_WooCommerce class file.
 *
 * @package Serbian Addons for WooCommerce
 */

namespace Oblak\WCSRB;

use Oblak\WCSRB\Services\Field_Validator;
use Oblak\WooCommerce\Serbian_Addons as Legacy;
use Oblak\WP\Decorators\Action;
use Oblak\WP\Decorators\Filter;
use Oblak\WP\Traits\Hook_Processor_Trait;
use XWC\Traits\Settings_API_Methods;
use XWP\Helper\Traits\Singleton;

/**
 * Main plugin class
 */
class App {
    use Hook_Processor_Trait;
    use Settings_API_Methods;
    use Singleton;
    use \XWP_Asset_Retriever;

    /**
     * Serbian WooCommerce version.
     *
     * @var string
     */
    public string $version = WCRS_VERSION;

    /**
     * Field validator instance.
     *
     * @var Field_Validator
     */
    protected Field_Validator $validator;

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
            Admin\Edit_User_Controller::class,
            Core\Address_Display_Controller::class,
            Core\Address_Field_Controller::class,
            Core\Address_Validate_Controller::class,
            Utils\Template_Extender::class,
            Legacy\Admin\Admin_Core::class,
        );
    }

    /**
     * Runs the registered hooks for the plugin.
     */
    public function run_hooks() {
        \xwp_invoke_hooked_methods( $this );
    }

    /**
     * Initializes the installer
     */
    #[Action( tag: 'plugins_loaded', priority: 1000 )]
    public function on_plugins_loaded() {
        Utils\Installer::instance()->init();

        \load_plugin_textdomain(
            domain: 'serbian-addons-for-woocommerce',
            plugin_rel_path: \dirname( WCRS_PLUGIN_BASE ) . '/languages',
        );
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
            \array_filter( $this->settings['core'] ?? array() ),
            array(
                'enabled_customer_types' => 'both',
                'fix_currency_symbol'    => true,
                'remove_unneeded_fields' => false,
            ),
        );

        $this->settings['company'] = array(
            'accounts'  => \wcsrb_get_bank_accounts(),
            'address'   => \get_option( 'woocommerce_store_address', '' ),
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
     * @param  string[] $gateways List of gateways.
     * @return string[]           Modified list of gateways.
     */
    #[Filter( tag: 'woocommerce_payment_gateways', priority: 50 )]
    public function add_payment_gateways( $gateways ) {
        $gateways[] = Legacy\Gateway\Gateway_Payment_Slip::class;
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

    /**
     * Gets the field validator instance.
     *
     * @return Field_Validator
     */
    public function validator(): Field_Validator {
        return $this->validator ??= new Field_Validator();
    }
}
