<?php
/**
 * Settings_Helper class file.
 *
 * @package Serbian Addons for WooCommerce
 * @subpackage Utils
 */

namespace Oblak\WCRS\Utils;

use Exception;

/**
 * Helper functions for plugin settings
 */
class Settings_Helper {

    /**
     * Parses all of the settings from the database into one option array
     *
     * @param  string $plugin_name Plugin option name.
     * @param  string $config_file Config file path.
     * @return array               Formatted plugin settings
     */
    public static function get_settings( $plugin_name, $config_file ) {
        global $wpdb;

        $options  = array();
        $defaults = include $config_file;

        $like     = $wpdb->esc_like( $plugin_name ) . '%';
        $not_like = '%' . $wpdb->esc_like( '[' ) . '%';

        $raw_opts = $wpdb->get_results(
            $wpdb->prepare( "SELECT option_name, option_value FROM {$wpdb->options} WHERE option_name LIKE %s AND option_name NOT LIKE %s", $like, $not_like ),
            ARRAY_A
        );

        foreach ( $raw_opts as $section ) {
            $key = str_replace( $plugin_name, '', $section['option_name'] );
            $key = ltrim( $key, '_' );

            if ( '' === $key ) {
                $key = 'general';
            }

            $options[ $key ] = array();
            $opts_array      = maybe_unserialize( $section['option_value'] );

            if ( is_array( $opts_array ) ) {

                $new_array = array();

                // Iterate over the options values, and convert them into multiple arrays.
                foreach ( $opts_array as $sub_key => $sub_value ) {

                    // If yes or no is found, convert it to boolean.
                    if ( in_array( $sub_value, array( 'yes', 'no' ), true ) ) {
                        $sub_value = 'yes' === $sub_value;
                    }

                    // Check if the option key has a dash in it, and explode if it does.
                    if ( strpos( $sub_key, '-' ) !== false ) {
                        $sub_key_arr = explode( '-', $sub_key );

                        // Left part of the exploded key is the array key, right part is the sub array key.
                        if ( ! array_key_exists( $sub_key_arr[0], $new_array ) ) {
                            $new_array[ $sub_key_arr[0] ] = array();
                        }

                        // Set the sub array key to the value.
                        $new_array[ $sub_key_arr[0] ][ $sub_key_arr[1] ] = $sub_value;
                        continue;
                    }

                    $new_array[ $sub_key ] = $sub_value;
                }

                $options[ $key ] = $new_array;
                continue;
            }

            $options[ $key ] = $opts_array;
        }

        return self::parse_args_r( $options, $defaults );
    }

    /**
     * Nested version of wp_parse_args
     *
     * @param array $a Array with options.
     * @param array $b Default array.
     */
    public static function parse_args_r( &$a, $b ) {
        $a = (array) $a;
        $b = (array) $b;
        $r = $b;

        foreach ( $a as $k => &$v ) {
            if ( is_array( $v ) && isset( $r[ $k ] ) ) {
                $r[ $k ] = self::parse_args_r( $v, $r[ $k ] );
            } else {
                $r[ $k ] = $v;
            }
        }

        return $r;
    }

}
