<?php
/**
 * Fiscalization_Page class file.
 *
 * @package Serbian Addons for WooCommerce
 * @subpackage WooCommerce\Admin\Settings
 */

namespace Oblak\WCRS\WooCommerce\Admin\Settings;

use WC_Payment_Gateway;
use WC_Settings_Page;

use function Oblak\WCRS\Utils\is_efiscalization_enabled;

/**
 * Fiscalization settings
 */
class Fiscalization_Page extends WC_Settings_Page {

    use Settings_Manager;

    /**
     * Class constructor
     */
    public function __construct() {
        $this->id          = 'wcsrb_fiscalization';
        $this->label       = __( 'eFiscalization', 'serbian-addons-for-woocommerce' );
        $this->config_file = WCRS_PLUGIN_PATH . 'config/settings-fiscalization.php';
        $this->settings    = $this->parse_settings();

        add_filter( 'woocommerce_get_sections_' . $this->id, array( $this, 'modify_sections' ) );
        add_filter( $this->id . '_formatted_settings', array( $this, 'remove_settings' ), 10, 2 );
        add_filter( $this->id . '_formatted_settings', array( $this, 'add_payment_settings' ), 10, 2 );

        $this->load_hooks();
        parent::__construct();
    }

    /**
     * Modifies sections if eFiscalization is disabled
     *
     * @param  array $sections Settings sections.
     * @return array           Modified settings sections.
     */
    public function modify_sections( $sections ) {
        if ( is_efiscalization_enabled() ) {
            return $sections;
        }

        return array_filter(
            $sections,
            function( $value, $key ) {
                return '' === $key;
            },
            ARRAY_FILTER_USE_BOTH
        );

    }

    /**
     * Removes plugin settings if the fiscalization is not enabled
     *
     * @param  array $settings Settings array.
     * @param  array $section  Section name.
     * @return array           Modified settings array.
     */
    public function remove_settings( $settings, $section ) {
        if ( is_efiscalization_enabled() || '' !== $section ) {
            return $settings;
        }

        return array_splice( $settings, 0, 3 );
    }

    /**
     * Dynamically adds payment settings for all enabled payment gateways
     *
     * @param  array  $settings Settings array.
     * @param  string $section  Section name.
     * @return array            Modified settings array.
     */
    public function add_payment_settings( $settings, $section ) {
        if ( ! is_efiscalization_enabled() || 'payments' !== $section ) {
            return $settings;
        }

        $dyn_settings  = array();
        $base_settings = include WCRS_PLUGIN_PATH . 'config/settings-payments.php';

        /**
         * Payment gateways
         *
         * @var WC_Payment_Gateway[] $gws
         */
        $gws = WC()->payment_gateways()->get_available_payment_gateways();

        foreach ( $gws as $gw_slug => $gw ) {
            $replace_array = array(
                '{{GWNAME}}' => $gw->get_title(),
                '{{GWSLUG}}' => $gw_slug,
            );

            foreach ( $base_settings as $setting_block ) {
                $gw_settings = array();

                foreach ( $setting_block as $key => $value ) {
                    $gw_settings[ $key ] = is_string( $value ) ? strtr( $value, $replace_array ) : $value;
                }

                $dyn_settings[] = $this->format_section_settings( $gw_settings, "{$this->id}_settings_{$section}" );

            }
        }

        return array_merge( $dyn_settings, $settings );

    }

}
