<?php
/**
 * Settings_Data_Handler class file.
 *
 * @package Serbian Addons for WooCommerce
 * @subpackage Admin
 */

namespace Oblak\WCSRB\Admin\Handlers;

use Oblak\WCSRB\Admin\Services\Settings_Page;
use XWP\DI\Decorators\Filter;
use XWP\DI\Decorators\Handler;

/**
 * Handles the settings customizations
 *
 * @since 4.0.0
 */
#[Handler( tag: 'init', priority: 0, context: Handler::CTX_ADMIN, container: 'wcsrb' )]
class Settings_Data_Handler {
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
                    'text' => \sprintf(
                        // Translators: %s is a link to the company settings page.
                        '<h2>' . \__(
                            'Store settings have been moved %s',
                            'serbian-addons-for-woocommerce',
                        ) . '</h2>',
                        \sprintf(
                            '<a href="%s">%s</a>',
                            \admin_url( 'admin.php?page=wc-settings&tab=wcsrb&section=company' ),
                            \__( 'here', 'serbian-addons-for-woocommerce' ),
                        ),
                    ),
                    'type' => 'info',
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
            include WCSRB_PATH . 'config/company-settings.php',
            $settings,
            array(
                array(
                    'id'   => 'store_address',
                    'type' => 'sectionend',
                ),
            ),
        );
    }

    /**
     * Santizes the bank accounts field.
     *
     * @param  array<string> $value     Sanitized value.
     * @return array<string>
     */
    #[Filter( tag: 'woocommerce_admin_settings_sanitize_option_woocommerce_store_bank_accounts', priority: 99 )]
    public function sanitize_bank_accounts_field( array $value ): array {
        foreach ( $value as &$acct ) {
            $acct = \Oblak\validateBankAccount( $acct )
                ? \wcsrb_format_bank_acct( $acct )
                : Settings_Page::add_acct_error( $acct );
        }

        return \array_values( \array_unique( \array_filter( $value ) ) );
    }
}
