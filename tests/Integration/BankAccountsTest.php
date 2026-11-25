<?php
/**
 * BankAccountsTest class file.
 *
 * @package Serbian Addons for WooCommerce
 * @subpackage Tests
 */

namespace Oblak\WCSRB\Tests\Integration;

use Oblak\WCSRB\Admin\Plugin_Settings_Page;
use Oblak\WCSRB\Tests\Support\TestCase;

/**
 * Store bank accounts: validation, company settings and the gateway account select.
 *
 * Account numbers below have hand-checked mod 97 check digits.
 */
class BankAccountsTest extends TestCase {
    public function test_otp_bank_accounts_are_valid(): void {
        $this->assertTrue( \wcsrb_validate_bank_account( '275-0000000123456-20' ) );
        $this->assertTrue( \wcsrb_validate_bank_account( '275-123456-20' ), 'Short format' );
        $this->assertTrue( \wcsrb_validate_bank_account( '275000000012345620' ), 'No separators' );
    }

    public function test_wrong_check_digits_or_unknown_banks_are_invalid(): void {
        $this->assertFalse( \wcsrb_validate_bank_account( '275-0000000123456-21' ) );
        $this->assertFalse( \wcsrb_validate_bank_account( '999-0000000123456-32' ), 'Valid check digits, unknown bank' );
        $this->assertFalse( \wcsrb_validate_bank_account( '' ) );
    }

    public function test_company_settings_save_otp_accounts(): void {
        $saved = $this->settings_page()->sanitize_bank_accounts_field( null, array(), array( '275-0000000123456-20', '160-0000000123456-54' ) );

        $this->assertSame( array( '275-123456-20', '160-123456-54' ), $saved );
    }

    public function test_company_settings_reject_invalid_accounts(): void {
        $saved = $this->settings_page()->sanitize_bank_accounts_field( null, array(), array( '275-0000000123456-21' ) );

        \ob_start();
        \WC_Admin_Settings::show_messages();
        $messages = \ob_get_clean();

        $this->assertSame( array(), $saved );
        $this->assertStringContainsString( 'Invalid bank account number: 275-0000000123456-21', $messages );
    }

    public function test_account_select_groups_accounts_by_bank(): void {
        $options = \wcsrb_format_bank_account_select( array( '160-123456-54', '275-123456-20' ) );

        $this->assertSame( array( '160-123456-54' => '160-123456-54' ), $options[ \wcsrb_get_serbian_banks()['160'] ] );
        $this->assertSame( array( '275-123456-20' => '275-123456-20' ), $options[ \wcsrb_get_serbian_banks()['275'] ] );
    }

    public function test_account_select_keeps_accounts_of_unknown_banks(): void {
        // E.g. saved before a bank merger removed its prefix from the list.
        $options = \wcsrb_format_bank_account_select( array( '999-123456-32' ) );

        $this->assertSame( 'Select bank account...', $options[''] );
        $this->assertSame( array( '999-123456-32' => '999-123456-32' ), $options['999'] );
    }

    /**
     * Build the plugin settings page (a wp-admin only WooCommerce settings page).
     *
     * @return Plugin_Settings_Page
     */
    private function settings_page(): Plugin_Settings_Page {
        require_once \WC_ABSPATH . 'includes/admin/settings/class-wc-settings-page.php';
        require_once \WC_ABSPATH . 'includes/admin/class-wc-admin-settings.php';

        return new Plugin_Settings_Page();
    }
}
