<?php
/**
 * InstallerTest class file.
 *
 * @package Serbian Addons for WooCommerce
 * @subpackage Tests
 */

namespace Oblak\WCSRB\Tests\Integration;

use Oblak\WCSRB\Tests\Support\TestCase;
use Oblak\WCSRB\Utils\Installer;

/**
 * Plugin install: the protected IPS QR code directory and the plugins screen link.
 */
class InstallerTest extends TestCase {
    /**
     * Remove the files the installer creates.
     */
    public function set_up(): void {
        parent::set_up();

        $this->remove_guard_files();
    }

    /**
     * Put the guard files back.
     */
    public function tear_down(): void {
        $this->remove_guard_files();
        Installer::instance()->setup_environment();

        parent::tear_down();
    }

    public function test_qr_code_directory_is_protected_from_direct_access(): void {
        Installer::instance()->setup_environment();

        $this->assertSame( 'deny from all', \file_get_contents( \WCRS_IPS_DIR . '/.htaccess' ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions
        $this->assertSame( '', \file_get_contents( \WCRS_IPS_DIR . '/index.html' ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions
    }

    public function test_existing_files_are_kept(): void {
        \file_put_contents( \WCRS_IPS_DIR . '/.htaccess', 'Require all denied' ); // phpcs:ignore WordPress.WP.AlternativeFunctions

        Installer::instance()->setup_environment();

        $this->assertSame( 'Require all denied', \file_get_contents( \WCRS_IPS_DIR . '/.htaccess' ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions
    }

    public function test_file_creation_can_be_skipped(): void {
        \add_filter( 'woocommerce_serbian_install_skip_create_files', '__return_true' );

        Installer::instance()->setup_environment();

        $this->assertFileDoesNotExist( \WCRS_IPS_DIR . '/.htaccess' );
        $this->assertFileDoesNotExist( \WCRS_IPS_DIR . '/index.html' );
    }

    public function test_plugins_screen_links_to_the_settings(): void {
        $links = \apply_filters( 'plugin_action_links_' . \WCRS_PLUGIN_BASE, array( 'deactivate' => '<a>Deactivate</a>' ) );

        $this->assertSame( array( 'settings', 'deactivate' ), \array_keys( $links ) );
        $this->assertStringContainsString( 'href="' . \admin_url( 'admin.php?page=wc-settings&tab=wcsrb' ) . '"', $links['settings'] );
    }

    /**
     * Delete the files that guard the QR code directory.
     */
    private function remove_guard_files(): void {
        foreach ( array( '.htaccess', 'index.html' ) as $file ) {
            if ( \file_exists( \WCRS_IPS_DIR . "/{$file}" ) ) {
                \unlink( \WCRS_IPS_DIR . "/{$file}" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink
            }
        }
    }
}
