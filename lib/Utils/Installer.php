<?php
/**
 * Installer class file.
 *
 * @package Serbian Addons for WooCommerce
 * @subpackage Core
 */

namespace Oblak\WCSRB\Utils;

use Oblak\WP\Base_Plugin_Installer;
use XWP\DI\Decorators\Action;
use XWP\DI\Decorators\Filter;
use XWP\DI\Decorators\Handler;

/**
 * Plugin installer class
 */
#[Handler( tag: 'plugins_loaded', priority: 1000, container: 'wcsrb' )]
class Installer extends Base_Plugin_Installer {
	/**
     * Constructor override
     */
    protected function __construct() {
        \add_action( 'plugins_loaded', array( $this, 'init' ), 1001 );

        parent::__construct();
    }

     // phpcs:ignore
    protected function set_defaults() {
        $this->name       = \__( 'Serbian Addons for WooCommerce', 'serbian-addons-for-woocommerce' );
        $this->slug       = 'serbian_woocommerce';
        $this->version    = WCRS_VERSION;
        $this->db_version = WCRS_VERSION;
    }

    /**
     * Load the plugin text domain for translation
     */
    #[Action( tag: 'plugins_loaded', priority: 1001 )]
    public function load_plugin_textdomain() {
        \load_plugin_textdomain(
            domain: 'serbian-addons-for-woocommerce',
            plugin_rel_path: \dirname( WCRS_PLUGIN_BASE ) . '/languages',
        );
    }



    /**
     * Show action links on the plugin screen
     *
     * @param  array $links Plugin Action links.
     * @return array        Modified action links
     */
    #[Filter( tag: 'plugin_action_links_%s', priority: 10, modifiers: WCRS_PLUGIN_BASE )]
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
                'base'    => \xwp_app( 'wcsrb' )->get( 'ips.basedir' ),
                'content' => '',
                'file'    => 'index.html',
            ),
            array(
                'base'    => \xwp_app( 'wcsrb' )->get( 'ips.basedir' ),
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
        if (
            ! \wp_mkdir_p( $file['base'] ) ||
            \file_exists( \trailingslashit( $file['base'] ) . $file['file'] )
        ) {
            return;
        }

        // phpcs:disable WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions
        $file_handle = @\fopen( \trailingslashit( $file['base'] ) . $file['file'], 'wb' );

        if ( ! $file_handle ) {
            return;
        }

        \fwrite( $file_handle, $file['content'] );
        \fclose( $file_handle );
        // phpcs:enable WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions
    }
}
