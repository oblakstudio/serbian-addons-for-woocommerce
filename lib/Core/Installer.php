<?php
/**
 * Installer class file.
 *
 * @package Serbian Addons for WooCommerce
 * @subpackage Core
 */

namespace Oblak\WCRS\Core;

use Oblak\WP\Base_Plugin_Installer;

/**
 * Plugin installer class
 */
class Installer extends Base_Plugin_Installer {

    /**
     * Class instance
     *
     * @var Base_Installer
     */
    protected static $instance = null;

    /**
     * Constructor override
     */
    protected function __construct() {
        add_action( 'plugin_action_links_' . WCRS_PLUGIN_BASENAME, array( __CLASS__, 'plugin_action_links' ) );

        parent::__construct();
    }

    // phpcs:ignore
    protected function set_defaults() {
        $this->name          = __( 'Serbian Addons for WooCommerce', 'serbian-addons-for-woocommerce' );
        $this->slug          = 'serbian_woocommerce';
        $this->version       = WCRS_VERSION;
        $this->db_version    = WCRS_VERSION;
        $this->has_db_tables = false;
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
}
