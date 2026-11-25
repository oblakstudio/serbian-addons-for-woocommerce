<?php
/**
 * SettingsPageTest class file.
 *
 * @package Serbian Addons for WooCommerce
 * @subpackage Tests
 */

namespace Oblak\WCSRB\Tests\Integration;

use Oblak\WCSRB\Admin\Plugin_Settings_Page;
use Oblak\WCSRB\Services\Config;
use Oblak\WCSRB\Tests\Support\Fixtures;
use Oblak\WCSRB\Tests\Support\TestCase;

/**
 * WooCommerce > Settings > Serbian Addons, and the store address moved off the General tab.
 */
class SettingsPageTest extends TestCase {
    /**
     * Load the wp-admin settings API.
     */
    public function set_up(): void {
        parent::set_up();

        require_once \WC_ABSPATH . 'includes/admin/settings/class-wc-settings-page.php';
        require_once \WC_ABSPATH . 'includes/admin/class-wc-admin-settings.php';

        $GLOBALS['current_tab'] = 'wcsrb';
    }

    /**
     * Reset the settings screen globals.
     */
    public function tear_down(): void {
        unset( $GLOBALS['current_tab'] );

        parent::tear_down();
    }

    public function test_page_has_general_and_company_sections(): void {
        $page = $this->page();

        $this->assertSame( 'wcsrb', $page->get_id() );
        $this->assertSame(
            array(
                ''        => 'General',
                'company' => 'Company information',
            ),
            $page->get_sections(),
        );
    }

    public function test_general_settings_are_stored_where_the_plugin_reads_them(): void {
        \update_option(
            'wcsrb_settings_core',
            array(
                'enabled_customer_types' => 'company',
                'fix_currency_symbol'    => 'no',
            ),
        );

        $fields = \array_column( $this->fields( '' ), null, 'id' );

        $this->assertSame( 'wcsrb_settings_core[enabled_customer_types]', $fields['enabled_customer_types']['field_name'] );
        $this->assertSame( 'company', $fields['enabled_customer_types']['value'] );
        $this->assertSame( array( 'both', 'person', 'company' ), \array_keys( $fields['enabled_customer_types']['options'] ) );
        $this->assertSame( 'no', $fields['fix_currency_symbol']['value'] );

        $config = new Config();

        $this->assertSame( 'company', $config->get( 'core', 'enabled_customer_types' ) );
        $this->assertFalse( $config->get( 'core', 'fix_currency_symbol' ) );
    }

    public function test_company_section_holds_the_store_address_and_bank_accounts(): void {
        $this->assertSame(
            array(
                'store_address',
                'woocommerce_store_name',
                'woocommerce_store_address',
                'woocommerce_store_address_2',
                'woocommerce_store_city',
                'woocommerce_default_country',
                'woocommerce_store_postcode',
                'woocommerce_store_bank_accounts',
                'store_address',
            ),
            \array_column( $this->fields( 'company' ), 'id' ),
        );
    }

    public function test_company_section_renders_the_saved_bank_accounts(): void {
        \update_option( 'woocommerce_store_bank_accounts', array( 'acct' => array( Fixtures::BANK_ACCOUNT_SHORT, '275-123456-20' ) ) );

        $html = $this->render( $this->fields( 'company' ) );

        \preg_match_all( '/<input\s+name="woocommerce_store_bank_accounts\[\]"[^>]*value="([^"]*)"/', $html, $rows );

        $this->assertSame( array( Fixtures::BANK_ACCOUNT_SHORT, '275-123456-20' ), $rows[1] );
        $this->assertStringContainsString( 'data-name="woocommerce_store_bank_accounts[]"', $html, 'Add row button' );
        $this->assertStringContainsString( '<script id="tmpl-woocommerce_store_bank_accounts"', $html, 'Row template' );
    }

    public function test_bank_account_rows_get_the_custom_attributes(): void {
        \ob_start();
        $this->page()->output_bank_accounts_field(
            array(
                'class'             => 'regular-input',
                'custom_attributes' => array( 'pattern' => '[0-9-]+' ),
                'desc'              => '',
                'desc_tip'          => false,
                'field_name'        => 'accounts',
                'id'                => 'accounts',
                'placeholder'       => '',
                'suffix'            => '',
                'title'             => 'Accounts',
                'type'              => 'repeater_text',
                'value'             => '160-123456-54',
            ),
        );
        $html = \ob_get_clean();

        $this->assertMatchesRegularExpression( '/<input\s+name="accounts\[\]"[^>]*value="160-123456-54"[^>]*pattern="\[0-9-\]\+"/', $html );
        $this->assertStringContainsString( 'data-custom_atts="pattern=&quot;[0-9-]+&quot;"', $html );
    }

    public function test_general_tab_links_to_the_company_settings_instead_of_the_store_address(): void {
        $this->page(); // Registers the settings page hooks.

        if ( ! \class_exists( \WC_Settings_General::class ) ) {
            include \WC_ABSPATH . 'includes/admin/settings/class-wc-settings-general.php';
        }

        $ids = \array_column( ( new \WC_Settings_General() )->get_settings_for_section( '' ), 'id' );
        $top = ( new \WC_Settings_General() )->get_settings_for_section( '' )[0];

        $this->assertSame( 'info', $top['type'] );
        $this->assertStringContainsString( 'href="' . \admin_url( 'admin.php?page=wc-settings&tab=wcsrb&section=company' ) . '"', $top['text'] );
        $this->assertNotContains( 'woocommerce_store_address', $ids, 'Store address is edited on the company settings' );
        $this->assertNotContains( 'woocommerce_default_country', $ids );
        $this->assertContains( 'woocommerce_allowed_countries', $ids, 'The rest of the general settings stay' );
    }

    public function test_other_general_sections_are_not_changed(): void {
        $settings = array( array( 'id' => 'woocommerce_store_address' ) );

        $this->assertSame( $settings, $this->page()->modify_general_settings( $settings, 'other' ) );
    }

    /**
     * Build the settings page, as WooCommerce does on its settings screen.
     *
     * @return Plugin_Settings_Page
     */
    private function page(): Plugin_Settings_Page {
        return $this->register_hooks( new Plugin_Settings_Page() );
    }

    /**
     * Get the fields of a settings section.
     *
     * @param  string $section Section ID.
     * @return array<int,array<string,mixed>>
     */
    private function fields( string $section ): array {
        $page = $this->page();
        $page->get_sections(); // Loads the settings, like the section tabs above the form.

        return $page->get_settings_for_section( $section );
    }

    /**
     * Render settings fields.
     *
     * @param  array<int,array<string,mixed>> $fields Fields.
     * @return string
     */
    private function render( array $fields ): string {
        \ob_start();
        \WC_Admin_Settings::output_fields( $fields );

        return \ob_get_clean();
    }
}
