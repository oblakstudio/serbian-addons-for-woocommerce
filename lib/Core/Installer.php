<?php
/**
 * Installer class file.
 *
 * @package Serbian Addons for WooCommerce
 * @subpackage Core
 */

namespace Oblak\WooCommerce\Serbian_Addons\Core;

use Oblak\WP\Base_Plugin_Installer;

/**
 * Plugin installer class
 */
class Installer extends Base_Plugin_Installer {
	/**
     * Constructor override
     */
    protected function __construct() {
        add_action( 'plugin_action_links_' . WCRS_PLUGIN_BASENAME, array( __CLASS__, 'plugin_action_links' ) );

        parent::__construct();
    }

    // phpcs:ignore
    protected function set_defaults() {
        $this->name       = __( 'Serbian Addons for WooCommerce', 'serbian-addons-for-woocommerce' );
        $this->slug       = 'serbian_woocommerce';
        $this->version    = WCRS_VERSION;
        $this->db_version = WCRS_VERSION;
    }

    /**
     * Show action links on the plugin screen
     *
     * @param  array $links Plugin Action links.
     * @return array        Modified action links
     */
    public static function plugin_action_links( $links ) {
        $action_links = array(
            'settings' => sprintf(
                '<a href="%s" aria-label="%s">%s</a>',
                admin_url( 'admin.php?page=wc-settings' ),
                esc_attr__( 'Settings', 'serbian-addons-for-woocommerce' ),
                esc_html__( 'Settings', 'serbian-addons-for-woocommerce' ),
            ),
        );

        return array_merge( $action_links, $links );
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
         * @return bool
		 * @since 3.4.0
		 */
		if ( apply_filters( 'woocommerce_serbian_install_skip_create_files', false ) ) {
			return;
		}

		// Install files and folders for uploading files and prevent hotlinking.
        $files = array(
            array(
				'base'    => WCRS_IPS_DIR,
				'file'    => 'index.html',
				'content' => '',
            ),
            array(
                'base'    => WCRS_IPS_DIR,
                'file'    => '.htaccess',
                'content' => 'deny from all',
            ),
        );

        foreach ( $files as $file ) {
			if ( wp_mkdir_p( $file['base'] ) && ! file_exists( trailingslashit( $file['base'] ) . $file['file'] ) ) {
				$file_handle = @fopen( trailingslashit( $file['base'] ) . $file['file'], 'wb' ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions.file_system_operations_fopen
				if ( $file_handle ) {
					fwrite( $file_handle, $file['content'] ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite
					fclose( $file_handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
				}
			}
		}
    }
}
