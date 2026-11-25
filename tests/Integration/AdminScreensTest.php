<?php
/**
 * AdminScreensTest class file.
 *
 * @package Serbian Addons for WooCommerce
 * @subpackage Tests
 */

namespace Oblak\WCSRB\Tests\Integration;

use Oblak\WCSRB\Admin\Admin_Core;
use Oblak\WCSRB\Admin\Plugin_Settings_Page;
use Oblak\WCSRB\Core\Address_Admin_Controller;
use Oblak\WCSRB\Tests\Support\TestCase;

/**
 * wp-admin integration: screen classes, the settings page, user profile and order list.
 */
class AdminScreensTest extends TestCase {
    /**
     * Load the wp-admin handlers.
     */
    public function set_up(): void {
        parent::set_up();

        $this->load_admin_handler( Admin_Core::class );
        $this->load_admin_handler( Address_Admin_Controller::class );
    }

    /**
     * Reset the screen globals.
     */
    public function tear_down(): void {
        unset( $GLOBALS['pagenow'], $GLOBALS['current_tab'], $GLOBALS['current_section'] );
        \set_current_screen( 'front' );

        parent::tear_down();
    }

    /**
     * Settings screens with plugin specific scripts and styles.
     *
     * @return array<string,array{string,string,string,string}>
     */
    public function provide_settings_screens(): array {
        return array(
            'company settings'   => array( 'admin.php', 'wcsrb', 'company', 'wcsrb-company-settings' ),
            'slip settings'      => array( 'admin.php', 'checkout', 'wcsrb_payment_slip', 'wcsrb-slip-settings' ),
            'general settings'   => array( 'admin.php', 'wcsrb', '', '' ),
            'other gateway'      => array( 'admin.php', 'checkout', 'bacs', '' ),
            'not a settings page' => array( 'edit.php', 'wcsrb', 'company', '' ),
        );
    }

    /**
     * @dataProvider provide_settings_screens
     *
     * @param string $pagenow  Admin page.
     * @param string $tab      Settings tab.
     * @param string $section  Settings section.
     * @param string $expected Expected plugin class, empty for none.
     */
    public function test_settings_screens_get_a_body_class( string $pagenow, string $tab, string $section, string $expected ): void {
        \set_current_screen( 'dashboard' );

        $GLOBALS['pagenow']         = $pagenow;
        $GLOBALS['current_tab']     = $tab;
        $GLOBALS['current_section'] = $section;

        $classes = \preg_split( '/\s+/', \apply_filters( 'admin_body_class', '' ), -1, \PREG_SPLIT_NO_EMPTY );
        $plugin  = \array_values( \array_filter( $classes, static fn( $c ) => \str_starts_with( $c, 'wcsrb-' ) ) );

        $this->assertSame( $expected ? array( $expected ) : array(), $plugin );
    }

    public function test_the_settings_page_is_added_to_woocommerce(): void {
        $page = new Plugin_Settings_Page();

        $this->assertSame( array( 'other', $page ), \xwp_app( 'wcsrb' )->get( Admin_Core::class )->add_settings_page( array( 'other' ), $page ) );
    }

    public function test_user_profile_lists_the_customer_fields_around_the_company(): void {
        require_once \WC_ABSPATH . 'includes/admin/class-wc-admin-profile.php';

        $keys    = \array_keys( ( new \WC_Admin_Profile() )->get_customer_meta_fields()['billing']['fields'] );
        $company = \array_search( 'billing_company', $keys, true );

        $this->assertSame( array( 'billing_type', 'billing_company', 'billing_mb', 'billing_pib' ), \array_slice( $keys, $company - 1, 4 ) );
        $this->assertSame( 'billing_last_name', $keys[ $company - 2 ] );
    }

    public function test_order_list_shows_serbian_companies_by_name(): void {
        $buyer = static fn( $order ) => \apply_filters( 'woocommerce_admin_order_buyer_name', 'Petar Petrović', $order );

        $this->assertSame( 'Firma d.o.o.', $buyer( $this->create_order( array( 'type' => 'company' ) ) ) );
        $this->assertSame( 'Petar Petrović', $buyer( $this->create_order() ), 'Person' );
        $this->assertSame( 'Petar Petrović', $buyer( $this->create_order( array( 'country' => 'DE', 'type' => 'company' ) ) ), 'Foreign company' );
    }
}
