<?php // phpcs:disable PHPCompatibility.Miscellaneous.RemovedAlternativePHPTags.MaybeASPOpenTagFound, Generic.PHP.DisallowAlternativePHPTags.MaybeASPShortOpenTagFound, SlevomatCodingStandard.Arrays.AlphabeticallySortedByKeys.IncorrectKeyOrder, SlevomatCodingStandard.Functions.RequireMultiLineCall.RequiredMultiLineCall
/**
 * Plugin_Settings_Page class file.
 *
 * @package Serbian Addons for WooCommerce
 */
namespace Oblak\WooCommerce\Serbian_Addons\Admin;

use Oblak\WooCommerce\Admin\Extended_Settings_Page;
use Oblak\WP\Decorators\Action;
use Oblak\WP\Decorators\Filter;
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
            \__( 'Serbian Addons', 'serbian-addons-for-woocommerce' ),
            include WCRS_PLUGIN_PATH . 'config/settings.php',
		);

        \xwp_invoke_hooked_methods( $this );
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
    #[Filter( tag: 'woocommerce_get_settings_general', priority: 99999999 )]
    public function modify_general_settings( $settings, $section ) {
        if ( '' !== $section ) {
            return $settings;
        }
        return \array_merge(
            array(
				array(
					'type' => 'info',
					'text' => \sprintf(
                        // Translators: %s is a link to the company settings page.
                        '<h2>' . \__( 'Store settings have been moved %s', 'serbian-addons-for-woocommerce' ) . '</h2>',
                        \sprintf(
                            '<a href="%s">%s</a>',
                            \admin_url( 'admin.php?page=wc-settings&tab=wcsrb&section=company' ),
                            \__( 'here', 'serbian-addons-for-woocommerce' ),
                        ),
                    ),
				),
			),
            \array_slice( $settings, 7 ),
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
    #[Filter( tag: 'woocommerce_formatted_settings_wcsrb', priority: 99 )]
    public function modify_company_settings( $settings, $section ) {
        if ( 'company' !== $section ) {
            return $settings;
        }

        return \array_merge(
            array(
                array(
                    'title' => \__( 'Company information', 'woocommerce' ),
                    'type'  => 'title',
                    'desc'  => \__( 'This is where your business is located. Tax rates and shipping rates will use this address.', 'woocommerce' ),
                    'id'    => 'store_address',
                ),

                array(
                    'title'    => \__( 'Business name', 'serbian-addons-for-woocommerce' ),
                    'desc'     => \__( 'Name of your business', 'serbian-addons-for-woocommerce' ),
                    'id'       => 'woocommerce_store_name',
                    'default'  => '',
                    'type'     => 'text',
                    'desc_tip' => true,
                ),

                array(
                    'title'    => \__( 'Address line 1', 'woocommerce' ),
                    'desc'     => \__( 'The street address for your business location.', 'woocommerce' ),
                    'id'       => 'woocommerce_store_address',
                    'default'  => '',
                    'type'     => 'text',
                    'desc_tip' => true,
                ),

                array(
                    'title'    => \__( 'Address line 2', 'woocommerce' ),
                    'desc'     => \__( 'An additional, optional address line for your business location.', 'woocommerce' ),
                    'id'       => 'woocommerce_store_address_2',
                    'default'  => '',
                    'type'     => 'text',
                    'desc_tip' => true,
                ),

                array(
                    'title'    => \__( 'City', 'woocommerce' ),
                    'desc'     => \__( 'The city in which your business is located.', 'woocommerce' ),
                    'id'       => 'woocommerce_store_city',
                    'default'  => '',
                    'type'     => 'text',
                    'desc_tip' => true,
                ),

                array(
                    'title'    => \__( 'Country / State', 'woocommerce' ),
                    'desc'     => \__( 'The country and state or province, if any, in which your business is located.', 'woocommerce' ),
                    'id'       => 'woocommerce_default_country',
                    'default'  => 'US:CA',
                    'type'     => 'single_select_country',
                    'desc_tip' => true,
                ),

                array(
                    'title'    => \__( 'Postcode / ZIP', 'woocommerce' ),
                    'desc'     => \__( 'The postal code, if any, in which your business is located.', 'woocommerce' ),
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
			),
        );
    }

    /**
     * Outputs the bank accounts field
     *
     * @param array $value Field data.
     */
    #[Action( tag: 'woocommerce_admin_field_repeater_text', priority: 10 )]
    public function output_bank_accounts_field( $value ) {
        $option_value      = \wc_string_to_array( $value['value'] ?? '' );
        $field_name        = "{$value['field_name']}[]";
        $field_description = WC_Admin_Settings::get_field_description( $value );
        $description       = $field_description['description'];
        $tooltip_html      = $field_description['tooltip_html'];
        $custom_attributes = array();

		if ( isset( $value['custom_attributes'] ) && \is_array( $value['custom_attributes'] ) ) {
			foreach ( $value['custom_attributes'] as $att_key => $att_val ) {
				$custom_attributes[] = \sprintf( '%s="%s"', \esc_attr( $att_key ), \esc_attr( $att_val ) );
			}
		}
        ?>
        <tr valign="top">
            <th scope="row" class="titledesc">
                <label for="<?php echo \esc_attr( $value['id'] ); ?>">
                    <?php echo esc_html( $value['title'] ); ?> <?php echo $tooltip_html; // phpcs:ignore ?>
                </label>
            </th>
            <td class="bank-accounts forminp forminp-<?php echo \esc_attr( \sanitize_title( $value['type'] ) ); ?>">
                <div id="<?php echo \esc_attr( $value['id'] ); ?>">
                <?php foreach ( $option_value ?? array() as $row_value ) : ?>
                    <div class="repeater-row row">
                        <input
                            name="<?php echo \esc_attr( $field_name ); ?>"
                            id="<?php echo \esc_attr( $value['id'] ); ?>"
                            type="text"
                            value="<?php echo \esc_attr( $row_value ); ?>"
                            class="<?php echo \esc_attr( $value['class'] ); ?>"
                            placeholder="<?php echo \esc_attr( $value['placeholder'] ); ?>"
                            <?php echo implode( ' ', $custom_attributes ); // phpcs:ignore ?>
                        />
                        <?php echo esc_html( $value['suffix'] ); ?> <?php echo $description; //phpcs:ignore ?>
                        <button type="button" class="button minus repeater-remove-row"><?php \esc_html_e( 'Remove', 'woocommerce' ); ?></button>
                    </div>
                <?php endforeach; ?>
                </div>
                <button
                    type="button"
                    class="button plus repeater-add-row"
                    data-tmpl="<?php echo \esc_attr( $value['id'] ); ?>-tmpl"
                    data-name="<?php echo \esc_attr( $field_name ); ?>"
                    data-type="text"
                    data-value=""
                    data-class="<?php echo \esc_attr( $value['class'] ); ?>"
                    data-placeholder="<?php echo \esc_attr( $value['placeholder'] ); ?>"
                    data-custom_atts="<?php echo \esc_attr( \implode( ' ', $custom_attributes ) ); ?>"
                    data-suffix="<?php echo \esc_attr( $value['suffix'] ); ?>"
                >
                    <?php \esc_html_e( 'Add', 'woocommerce' ); ?>
                </button>
            </td>
            <script id="<?php echo \esc_attr( $value['id'] ); ?>-tmpl" type="text/html" class="repeater-tmpl">
                <div class="repeater-row row">
                    <input
                        name="<%= data.name %>"
                        type="<%= data.type %>"
                        value="<%= data.value %>"
                        class="<%= data.class %>"
                        placeholder="<%= data.placeholder %>"
                        <%= data.custom_atts %>
                    ><%= data.suffix %>
                    <button type="button" class="button minus repeater-remove-row">
                        <?php \esc_html_e( 'Remove', 'woocommerce' ); ?>
                    </button>
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
    #[Filter( tag: 'woocommerce_admin_settings_sanitize_option_woocommerce_store_bank_accounts', priority: 99 )]
    public function sanitize_bank_accounts_field( $value, $option, $raw_value ) {
        $value = array();

        foreach ( \wc_string_to_array( $raw_value ) as $acct ) {
            $value[] = \Oblak\validateBankAccount( $acct )
                ? \wcsrb_format_bank_acct( $acct )
                : $this::add_acct_error( $acct );
        }

        return \array_values( \array_unique( \array_filter( $value ) ) );
    }

    /**
     * Adds an error message for an invalid bank account number.
     *
     * @param  string $acct The invalid bank account number.
     * @return null
     */
    public static function add_acct_error( string $acct ) {
        static $added = array();

        if ( ! \in_array( $acct, $added, true ) ) {
            \WC_Admin_Settings::add_error(
                \sprintf(
                    // Translators: %s is the invalid bank account number.
                    \__( 'Invalid bank account number: %s', 'serbian-addons-for-woocommerce' ),
                    $acct,
                ),
            );

            $added[] = $acct;
        }

        return null;
    }
}
