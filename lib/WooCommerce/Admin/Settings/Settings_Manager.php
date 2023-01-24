<?php
/**
 * Settings_Manager trait file
 *
 * @package Serbian Addons for WooCommerce
 * @subpackage WooCommerce\Admin\Settings
 */

namespace Oblak\WCRS\WooCommerce\Admin\Settings;

use Exception;

/**
 * Handles nested settings saving and output
 */
trait Settings_Manager {

    /**
     * Config file path
     *
     * @var string
     */
    protected $config_file = '';

    /**
     * Settings array
     *
     * @var array
     */
    protected $settings = array();

    /**
     * Nested fields array
     *
     * @var array
     */
    protected $nested_fields = array();

    /**
     * Parses settings from config file
     *
     * @return array Settings array
     *
     * @throws Exception If settings file does not exist.
     */
    protected function parse_settings() {
        if ( ! file_exists( $this->config_file ) ) {
            throw new Exception( 'Settings file does not exist.' );
        }

        $base_settings = include $this->config_file;

        /**
         * Filter the settings array
         *
         * @since 4.0.0
         * @param array $base_settings Base settings array
         */
        $settings = apply_filters( $this->id . '_settings', $base_settings );

        uasort(
            $settings,
            function( $a, $b ) {
                return $a['priority'] - $b['priority'];
            }
        );

        return $settings;
    }

    /**
     * Get own sections for this page.
     * Derived classes should override this method if they define sections.
     * There should always be one default section with an empty string as identifier.
     *
     * Example:
     * return array(
     *   ''        => __( 'General', 'woocommerce' ),
     *   'foobars' => __( 'Foos & Bars', 'woocommerce' ),
     * );
     *
     * @return array An associative array where keys are section identifiers and the values are translated section names.
     */
    protected function get_own_sections() {
        foreach ( $this->settings as $section => $data ) {
            if ( ! $data['enabled'] ) {
                continue;
            }
            $sections[ $section ] = $data['section_name'];
        }

        return $sections;
    }

    /**
     * Loads hooks needed for settings manager to work
     */
    public function load_hooks() {
        add_filter( 'woocommerce_get_settings_' . $this->id, array( $this, 'get_custom_settings' ), 10, 2 );
        // add_filter( 'woocommerce_admin_settings_sanitize_option', array( $this, 'sanitize_nested_array' ), 99, 3 );
    }

    /**
     * Retrives the settings fields
     *
     * @param array  $settings Settings array.
     * @param string $section  Settings section ID.
     *
     * @return array $settings Array of plugin settings fields
     */
    public function get_custom_settings( $settings, $section ) {
        $settings_name = '' !== $section ? "{$this->id}_settings_{$section}" : "{$this->id}_settings_general";

        $settings = array_map(
            function ( $block ) use ( $settings_name ) {
                return $this->format_section_settings( $block, $settings_name );
            },
            $this->settings[ $section ]['fields']
        );

        /**
         * Filters the formated settings for the plugin
         *
         * @since 2.2.0
         * @param array $settings Formated settings array
         */
        return apply_filters( "{$this->id}_formatted_settings", $settings, $section );

    }

    /**
     * Settings ID formatter
     *
     * We prefix the settings ID with the plugin settings section ID.
     *
     * @param  array  $setting_block Settings block.
     * @param  string $name          Section name.
     * @return array                 Formatted settings block
     */
    private function format_section_settings( $setting_block, $name ) {
        if ( in_array( $setting_block['type'], array( 'title', 'sectionend' ), true ) ) {
            return $setting_block;
        }

        $setting_block['id'] = "{$name}[{$setting_block['id']}]";

        if ( 'select' === $setting_block['type'] && array_key_exists( 'multiple', ( $setting_block['custom_attributes'] ?? array() ) ) ) {
            $setting_block['id'] .= '[]';
        }

        return $setting_block;
    }

    /**
     * Sanitizes the size array settings.
     *
     * WooCommerce doesn't like nested arrays so we have to fuck with it
     *
     * @param  mixed $value     Value to sanitize.
     * @param  array $option    Option array.
     * @param  mixed $raw_value Raw value.
     * @return mixed|array      Sanitized value
     */
    public function sanitize_nested_array( $value, $option, $raw_value ) {
        $nested = false;
        foreach ( $this->nested_fields as $nested_field ) {
            if ( strpos( $option['id'], "[{$nested_field}]" ) !== false ) {
                $nested = true;
            }
        }

        if ( $nested ) {
            return array_map( 'sanitize_text_field', $raw_value );
        }

        if ( strpos( $option['id'], '[' ) !== false && strpos( $option['id'], $this->id ) !== false ) {
            return null;
        }
        return $value;
    }


}
