<?php
/**
 * Custom Fields Class file
 *
 * @package Serbian Addons for WooCommerce
 * @subpackage WooCommerce\Admin\Settings
 */

namespace Oblak\WCRS\WooCommerce\Admin\Settings;

use DateTime;

/**
 * Handles custom fields output and saving
 */
class Custom_Fields {

    /**
     * Class constructor
     */
    public function __construct() {
        add_action( 'woocommerce_admin_field_wcsrb_fiscalization_constant_field', array( $this, 'api_key_field' ), 0, 1 );
        add_action( 'woocommerce_admin_field_wcsrb_fiscalization_connection_status', array( $this, 'connection_status' ), 0, 0 );
        add_action( 'woocommerce_admin_field_wcsrb_fiscalization_tax_rates', array( $this, 'tax_rates' ), 0, 0 );
    }

    /**
     * Callback function for the API key field
     *
     * We need to override the default WooCommerce field to add an option to configure the field via constant
     *
     * @param array $value The field value.
     */
    public function api_key_field( array $value ) {
        $option_value = $value['value'];
        $attributes   = array();
        $description  = sprintf(
            '<p class="description">%s</p>',
            __(
                "This field will be stored in plain text.
                We recommend using your site's WordPress configuration file to set it.",
                'serbian-addons-for-woocommerce'
            )
        );

        // echo '<pre>';
        // var_dump( $value );
        // die;

        if ( defined( $value['core'] ) && defined( $value['constant'] ) ) {
            $description = sprintf(
                '<p class="description">
                    %s <code>%s</code> %s <code>wp-config.php</code> %s.
                </p>
                <p class="description">
                    %s: <code>define (\'%s\', \'something\');</code><br>
                    %s <code>wp-config.php</code> %s <code>%s</code> %s:
                </p>
                <pre>define (\'%s\', false);</pre>
                <p class="description">%s</p>',
                __( 'The value of this field was set using a constant', 'serbian-addons-for-woocommerce' ),
                $value['constant'],
                __( 'most likely inside', 'serbian-addons-for-woocommerce' ),
                __( 'of your WordPress installation', 'serbian-addons-for-woocommerce' ),
                __(
                    'To change the api key you need to change the value of the constant there',
                    'serbian-addons-for-woocommerce'
                ),
                $value['constant'],
                __( 'If you want to disable the use of constants, find in', 'serbian-addons-for-woocommerce' ),
                __( 'file the constant', 'serbian-addons-for-woocommerce' ),
                $value['core'],
                __( 'and turn if off', 'serbian-addons-for-woocommerce' ),
                $value['core'],
                __(
                    'All the constants will stop working and you will be able to change all the values on this page.',
                    'serbian-addons-for-woocommerce'
                )
            );

            $attributes[] = 'disabled="disabled"';
            $option_value = '***************';
        }

        $description = '<div class="italicize">' . $description . '</div>'

        ?>
        <style type="text/css">
        .italicize p {
            font-style:italic;
            margin-bottom:10px !important;
        }
        </style>
        <tr valign="top">
            <th scope="row" class="titledesc">
                <label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?></label>
            </th>
            <td class="forminp forminp-text">
                <input
                    name="<?php echo esc_attr( $value['id'] ); ?>"
                    id="<?php echo esc_attr( $value['id'] ); ?>"
                    type="password"
                    style="<?php echo esc_attr( $value['css'] ); ?>"
                    value="<?php echo esc_attr( $option_value ); ?>"
                    class="<?php echo esc_attr( $value['class'] ); ?>"
                    placeholder="<?php echo esc_attr( $value['placeholder'] ); ?>"
        <?php echo implode( ' ', $attributes ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                >
        <?php
        echo esc_html( $value['suffix'] );
        echo $description; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        ?>
            </td>
        </tr>
        <?php
    }

    /**
     * Outputs the connection status table
     */
    public function connection_status() {
        $field_map = array(
            'status'       => __( 'Status', 'serbian-addons-for-woocommerce' ),
            'availability' => __( 'Availability', 'serbian-addons-for-woocommerce' ),
            'version'      => __( 'Version', 'serbian-addons-for-woocommerce' ),
            'author'       => __( 'Author', 'serbian-addons-for-woocommerce' ),
            'serialNo'     => __( 'Serial number', 'serbian-addons-for-woocommerce' ),
        );
        $status    = WCSRB()->get_efis_client()->get_connection_status();

        if ( is_wp_error( $status ) ) {
            echo esc_html( $status->get_error_message() );
            return;
        }

        ?>
        <tr valign="top">
            <td style="padding: 0;" colspan="2">
                <div class="efis-status" style="max-width: 500px">
                    <table class="widefat striped">
                        <tbody>
                            <?php foreach ( $status as $key => $value ) : ?>
                                <tr>
                                    <td><strong><?php echo esc_html( $field_map[ $key ] ); ?></strong></td>
                                    <td><?php echo esc_html( $value ); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </td>
        <tr>
        <?php
    }

    /**
     * Outputs the available tax rates table
     */
    public function tax_rates() {
        $tax_rates = WCSRB()->get_efis_client()->get_tax_rates();

        if ( is_wp_error( $tax_rates ) ) {
            echo esc_html( $tax_rates->get_error_message() );
            return;
        }

        $tax_rates = $tax_rates['currentTaxRates'];
        $date      = new DateTime( $tax_rates['validFrom'] );

        ?>
        <tr valign="top">
            <td style="padding: 0;" colspan="2">
                <div class="efis-tax-rates" style="max-width: 500px">
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th>&nbsp;<?php esc_html_e( 'Category name', 'serbian-addons-for-woocommerce' ); ?></th>
                                <th>&nbsp;<?php esc_html_e( 'Tax rates', 'serbian-addons-for-woocommerce' ); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ( $tax_rates['taxCategories'] as $tax_category ) : ?>
                                <tr>
                                    <td><?php echo esc_html( $tax_category['name'] ); ?></td>
                                    <td>
                                    <?php foreach ( $tax_category['taxRates'] as $tax_rate ) : ?>
                                        <?php echo esc_html( $tax_rate['label'] ); ?>: <?php echo esc_html( $tax_rate['rate'] ); ?>%<br>
                                    <?php endforeach; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="italicize">
                    <p class="description">
                        <?php
                        printf(
                            /* translators: %s: date */
                            esc_html__( 'Tax rates are valid from %s', 'serbian-addons-for-woocommerce' ),
                            esc_html( $date->format( 'd.m.Y' ) )
                        );
                        ?>
                    </p>
                </div>
            </td>
        <tr>
        <?php
    }

}
