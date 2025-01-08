<?php //phpcs:disable SlevomatCodingStandard.Functions.RequireMultiLineCall.RequiredMultiLineCall
/**
 * Installer class file.
 *
 * @package Serbian Addons for WooCommerce
 * @subpackage Core
 */

namespace Oblak\WCSRB\Core\Services;

use Automattic\WooCommerce\Utilities\FeaturesUtil;
use Oblak\WP\Base_Plugin_Installer;
use XWP\DI\Decorators\Action;
use XWP\DI\Decorators\Filter;
use XWP\DI\Decorators\Handler;

/**
 * Plugin installer class
 */
#[Handler( tag: 'plugins_loaded', priority: 999, container: 'wcsrb' )]
class Installer extends Base_Plugin_Installer {
    /**
     * Constructor override
     */
    protected function __construct() {
        \add_action( 'plugins_loaded', array( $this, 'init' ), 1001 );

        parent::__construct();
    }

    /**
     * Set default values
     *
     * @return void
     */
    protected function set_defaults() {
        //phpcs:ignore SlevomatCodingStandard.Functions.RequireMultiLineCall.RequiredMultiLineCall
        $this->tr_name    = static fn() => \__( 'Serbian Addons for WooCommerce', 'serbian-addons-for-woocommerce' );
        $this->slug       = 'serbian_woocommerce';
        $this->version    = WCSRB_VER;
        $this->db_version = WCSRB_VER;
    }

    /**
     * Load the plugin text domain for translation
     */
    #[Action( tag: 'init', priority: 1001 )]
    public function load_plugin_textdomain() {
        \load_plugin_textdomain(
            domain: 'serbian-addons-for-woocommerce',
            plugin_rel_path: \dirname( WCSRB_BASE ) . '/languages',
        );
    }

    /**
     * Declares compatibility with WooCommerce HPOS
     */
    #[Action( tag: 'before_woocommerce_init', priority: 10 )]
    public function declare_hpos_compatibility() {
        FeaturesUtil::declare_compatibility( 'custom_order_tables', WCSRB_FILE, true );
        FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', WCSRB_FILE, true );
    }


    /**
     * Show action links on the plugin screen
     *
     * @param  array $links Plugin Action links.
     * @return array        Modified action links
     */
    #[Filter( tag: 'plugin_action_links_%s', priority: 10, modifiers: WCSRB_BASE )]
    public function plugin_action_links( $links ) {
        $action_links = array(
            'settings' => \sprintf(
                '<a href="%s" aria-label="%s">%s</a>',
                \admin_url( 'admin.php?page=wc-settings&tab=wcsrb' ),
                \esc_attr__( 'Settings', 'serbian-addons-for-woocommerce' ),
                \esc_html__( 'Settings', 'serbian-addons-for-woocommerce' ),
            ),
        );

        return \array_merge( $action_links, $links );
    }

    /**
     * {@inheritDoc}
     */
    public function setup_environment() {
        $this->create_files();
    }

    /**
     * Create files and folders
     *
     * @return void
     */
    protected function create_files() {
        /**
         * Bypass if filesystem is read-only and/or non-standard upload system is used.
         *
         * @param  bool $skip_create_files Whether to skip creating files. Default is false.
         * @return bool
         * @since 3.4.0
         */
        if ( \apply_filters( 'woocommerce_serbian_install_skip_create_files', false ) ) {
            return;
        }

        // Install files and folders for uploading files and prevent hotlinking.
        $files = array(
            array(
                'base'    => \xwp_app( 'wcsrb' )->get( 'ips.dir' ),
                'content' => '',
                'file'    => 'index.html',
            ),
            array(
                'base'    => \xwp_app( 'wcsrb' )->get( 'ips.dir' ),
                'content' => 'deny from all',
                'file'    => '.htaccess',
            ),
        );

        foreach ( $files as $file ) {
            $this->create_file( $file );
        }
    }

    /**
     * Creates a file.
     *
     * @param  array $file File data.
     */
    private function create_file( array $file ) {
        if ( ! \wp_mkdir_p( $file['base'] ) || \file_exists( \trailingslashit( $file['base'] ) . $file['file'] ) ) {
            return;
        }

        // phpcs:disable WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions
        $file_handle = @\fopen( $filename, 'wb' );

        if ( ! $file_handle ) {
            return;
        }

        \fwrite( $file_handle, $file['content'] );
        \fclose( $file_handle );
        // phpcs:enable WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions
    }
}
