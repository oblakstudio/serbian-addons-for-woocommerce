<?php // phpcs:disable PHPCompatibility.Miscellaneous.RemovedAlternativePHPTags.MaybeASPOpenTagFound, Generic.PHP.DisallowAlternativePHPTags.MaybeASPShortOpenTagFound, SlevomatCodingStandard.Arrays.AlphabeticallySortedByKeys.IncorrectKeyOrder, SlevomatCodingStandard.Functions.RequireMultiLineCall.RequiredMultiLineCall
/**
 * Plugin_Settings_Page class file.
 *
 * @package Serbian Addons for WooCommerce
 */
namespace Oblak\WooCommerce\Serbian_Addons\Admin;

use Oblak\WP\Decorators\Action;
use Oblak\WP\Decorators\Filter;

/**
 * Adds the settings for the plugin to the WooCommerce settings page
 *
 * @since 2.2.0
 */
class Plugin_Settings_Page extends \XWC\Admin\Settings_Page_Base {
    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct(
            'wcsrb',
            \__( 'Serbian Addons', 'serbian-addons-for-woocommerce' ),
        );

        \xwp_invoke_hooked_methods( $this );
    }

    /**
     * Returns the settings array
     *
     * @return array[] Settings array
     */
    protected function get_settings_array(): array {
        return include WCRS_PLUGIN_PATH . 'config/settings.php';
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
    #[Filter( tag: 'woocommerce_get_settings_wcsrb', priority: 99 )]
    public function modify_company_settings( $settings, $section ) {
        if ( 'company' !== $section ) {
            return $settings;
        }

        return \array_merge(
            include WCRS_PLUGIN_PATH . 'config/company-settings.php',
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
        $option_value      = $value['value']['acct'] ?? $value['value'] ?? '';
        $option_value      = \wc_string_to_array( $option_value );
        $custom_attributes = array();

		if ( isset( $value['custom_attributes'] ) && \is_array( $value['custom_attributes'] ) ) {
			foreach ( $value['custom_attributes'] as $att_key => $att_val ) {
				$custom_attributes[] = \sprintf( '%s="%s"', \esc_attr( $att_key ), \esc_attr( $att_val ) );
			}
		}

        include __DIR__ . '/Views/admin-html-bank-account-field.php';
        include __DIR__ . '/Views/admin-html-bank-account-template.php';
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

        if ( ! \in_array( $acct, $added, true ) && '' !== $acct ) {
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
