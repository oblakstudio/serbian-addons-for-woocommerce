<?php //phpcs:disable PHPCompatibility.Miscellaneous.RemovedAlternativePHPTags.MaybeASPOpenTagFound, Generic.PHP.DisallowAlternativePHPTags.MaybeASPShortOpenTagFound
/**
 * Plugin_Settings_Page class file.
 *
 * @package Serbian Addons for WooCommerce
 */
namespace Oblak\WCRS\WooCommerce\Admin;

use Oblak\WooCommerce\Admin\Extended_Settings_Page;
use WC_Admin_Settings;

/**
 * Adds the settings for the plugin to the WooCommerce settings page
 *
 * @since 2.2.0
 */
class Plugin_Settings_Page extends Extended_Settings_Page {
    /**
     * Class Constructor
     */
    public function __construct() {
        parent::__construct(
            'wcsrb',
            __( 'Serbian Addons', 'serbian-addons-for-woocommerce' ),
            include WCRS_PLUGIN_PATH . 'config/settings.php'
		);

        add_filter( 'woocommerce_get_settings_general', array( $this, 'modify_general_settings' ), PHP_INT_MAX, 2 );
        add_filter( 'woocommerce_formatted_settings_wcsrb', array( $this, 'modify_company_settings' ), 99, 2 );
        add_action( 'woocommerce_admin_field_repeater_text', array( $this, 'output_bank_accounts_field' ), 10, 1 );
        add_filter( 'woocommerce_admin_settings_sanitize_option_woocommerce_store_bank_accounts', array( $this, 'sanitize_bank_accounts_field' ), 99, 3 );
    }

    /**
     * Modifies the general settings
     *
     * Removes the company settings section and adds a link to the company settings page
     *
     * @param  array[] $settings Settings fields.
     * @param  string  $section  Section name.
     * @return array[]           Modified settings fields
     */
    public function modify_general_settings( $settings, $section ) {
        if ( '' !== $section ) {
            return $settings;
        }
        return array_merge(
            array(
				array(
					'type'  => 'info',
					'title' => 'Waka waka',
					'text'  => sprintf(
                        // Translators: %s is a link to the company settings page.
                        '<h2>' . __( 'Store settings have been moved %s', 'serbian-addons-for-woocommerce' ) . '</h2>',
                        sprintf(
                            '<a href="%s">%s</a>',
                            admin_url( 'admin.php?page=wc-settings&tab=wcsrb&section=company' ),
                            __( 'here', 'serbian-addons-for-woocommerce' )
                        )
                    ),
				),
			),
            array_slice( $settings, 7 )
        );
    }

    /**
     * Adds the company settings section
     *
     * Since we use the extended settings page class, we need to add the section manually
     *
     * @param  array[] $settings Settings fields.
     * @param  string  $section  Section name.
     * @return array[]           Modified settings fields
     */
    public function modify_company_settings( $settings, $section ) {
        if ( 'company' !== $section ) {
            return $settings;
        }

        return array_merge(
            array(
                array(
                    'title' => __( 'Company information', 'woocommerce' ),
                    'type'  => 'title',
                    'desc'  => __( 'This is where your business is located. Tax rates and shipping rates will use this address.', 'woocommerce' ),
                    'id'    => 'store_address',
                ),

                array(
                    'title'    => __( 'Business name', 'serbian-addons-for-woocommerce' ),
                    'desc'     => __( 'Name of your business', 'serbian-addons-for-woocommerce' ),
                    'id'       => 'woocommerce_store_name',
                    'default'  => '',
                    'type'     => 'text',
                    'desc_tip' => true,
                ),

                array(
                    'title'    => __( 'Address line 1', 'woocommerce' ),
                    'desc'     => __( 'The street address for your business location.', 'woocommerce' ),
                    'id'       => 'woocommerce_store_address',
                    'default'  => '',
                    'type'     => 'text',
                    'desc_tip' => true,
                ),

                array(
                    'title'    => __( 'Address line 2', 'woocommerce' ),
                    'desc'     => __( 'An additional, optional address line for your business location.', 'woocommerce' ),
                    'id'       => 'woocommerce_store_address_2',
                    'default'  => '',
                    'type'     => 'text',
                    'desc_tip' => true,
                ),

                array(
                    'title'    => __( 'City', 'woocommerce' ),
                    'desc'     => __( 'The city in which your business is located.', 'woocommerce' ),
                    'id'       => 'woocommerce_store_city',
                    'default'  => '',
                    'type'     => 'text',
                    'desc_tip' => true,
                ),

                array(
                    'title'    => __( 'Country / State', 'woocommerce' ),
                    'desc'     => __( 'The country and state or province, if any, in which your business is located.', 'woocommerce' ),
                    'id'       => 'woocommerce_default_country',
                    'default'  => 'US:CA',
                    'type'     => 'single_select_country',
                    'desc_tip' => true,
                ),

                array(
                    'title'    => __( 'Postcode / ZIP', 'woocommerce' ),
                    'desc'     => __( 'The postal code, if any, in which your business is located.', 'woocommerce' ),
                    'id'       => 'woocommerce_store_postcode',
                    'css'      => 'min-width:50px;',
                    'default'  => '',
                    'type'     => 'text',
                    'desc_tip' => true,
                ),
            ),
            $settings,
            array(
				array(
					'type' => 'sectionend',
					'id'   => 'store_address',
				),
			)
        );
    }

    /**
     * Outputs the bank accounts field
     *
     * @param array $value Field data.
     */
    public function output_bank_accounts_field( $value ) {
        $option_value      = ! empty( $value['value'] ) ? $value['value'] : array();
        $field_description = WC_Admin_Settings::get_field_description( $value );
        $description       = $field_description['description'];
        $tooltip_html      = $field_description['tooltip_html'];
        $custom_attributes = array();
		if ( ! empty( $value['custom_attributes'] ) && is_array( $value['custom_attributes'] ) ) {
			foreach ( $value['custom_attributes'] as $attribute => $attribute_value ) {
				$custom_attributes[] = esc_attr( $attribute ) . '="' . esc_attr( $attribute_value ) . '"';
			}
		}
        ?>
        <tr valign="top">
            <th scope="row" class="titledesc">
                <label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?> <?php echo $tooltip_html; // phpcs:ignore ?></label>
            </th>
            <td class="bank-accounts forminp forminp-<?php echo esc_attr( sanitize_title( $value['type'] ) ); ?>">
                <div id="<?php echo esc_attr( $value['id'] ); ?>">
                <?php foreach ( $option_value['acct'] as $row_value ) : ?>
                    <div class="repeater-row row">
                        <input
                            name="<?php echo esc_attr( $value['field_name'] ); ?>"
                            id="<?php echo esc_attr( $value['id'] ); ?>"
                            type="text"
                            value="<?php echo esc_attr( $row_value ); ?>"
                            class="<?php echo esc_attr( $value['class'] ); ?>"
                            placeholder="<?php echo esc_attr( $value['placeholder'] ); ?>"
                            <?php echo implode( ' ', $custom_attributes ); // phpcs:ignore ?>
                        />
                        <?php echo esc_html( $value['suffix'] ); ?> <?php echo $description; //phpcs:ignore ?>
                        <button type="button" class="button minus repeater-remove-row"><?php esc_html_e( 'Remove', 'woocommerce' ); ?></button>
                    </div>
                <?php endforeach; ?>
                </div>
                <button
                    type="button"
                    class="button plus repeater-add-row"
                    data-tmpl="<?php echo esc_attr( $value['id'] ); ?>-tmpl"
                    data-name="<?php echo esc_attr( $value['field_name'] ); ?>"
                    data-type="text"
                    data-value=""
                    data-class="<?php echo esc_attr( $value['class'] ); ?>"
                    data-placeholder="<?php echo esc_attr( $value['placeholder'] ); ?>"
                    data-custom_atts="<?php echo wc_esc_json( wp_json_encode( $custom_attributes ) ); ?>"
                    data-suffix="<?php echo esc_attr( $value['suffix'] ); ?>"
                >
                    <?php esc_html_e( 'Add', 'woocommerce' ); ?>
                </button>
            </td>
            <script id="<?php echo esc_attr( $value['id'] ); ?>-tmpl" type="text/html" class="repeater-tmpl">
                <div class="repeater-row row">
                    <input
                        name="<%= data.name %>"
                        type="<%= data.type %>"
                        value="<%= data.value %>"
                        class="<%= data.class %>"
                        placeholder="<%= data.placeholder %>"
                        <%= data.custom_atts %>
                    ><%= data.suffix %>
                    <button type="button" class="button minus repeater-remove-row"><?php esc_html_e( 'Remove', 'woocommerce' ); ?></button>
                </div>
            </script>
        </tr>
        <?php
    }

	/**
	 * Santizes the bank accounts field.
	 *
	 * @param  mixed $value     Sanitized value.
	 * @param  array $option    Option array.
	 * @param  mixed $raw_value Raw value.
	 */
    public function sanitize_bank_accounts_field( $value, $option, $raw_value ) {
        return array_filter( $raw_value ?? array(), '\Oblak\validateBankAccount' );
    }
}
