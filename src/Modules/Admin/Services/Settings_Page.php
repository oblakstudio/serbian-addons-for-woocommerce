<?php // phpcs:disable PHPCompatibility.Miscellaneous.RemovedAlternativePHPTags.MaybeASPOpenTagFound, Generic.PHP.DisallowAlternativePHPTags.MaybeASPShortOpenTagFound, SlevomatCodingStandard.Arrays.AlphabeticallySortedByKeys.IncorrectKeyOrder, SlevomatCodingStandard.Functions.RequireMultiLineCall.RequiredMultiLineCall
/**
 * Plugin_Settings_Page class file.
 *
 * @package Serbian Addons for WooCommerce
 */

namespace Oblak\WCSRB\Admin\Services;

/**
 * Adds the settings for the plugin to the WooCommerce settings page
 *
 * @since 2.2.0
 */
class Settings_Page extends \XWC_Settings_Page {
    /**
     * Returns the settings array
     *
     * @return array<string,array<string,mixed>>
     */
    protected function get_settings_array(): array {
        return include WCSRB_PATH . 'config/settings.php';
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
