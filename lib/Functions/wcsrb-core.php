<?php // phpcs:disable WordPress.NamingConventions.ValidFunctionName.FunctionNameInvalid
/**
 * Core utility functions
 *
 * @package Serbian Addons for WooCommerce
 * @subpackage Utils
 */

/**
 * Initializes the plugin
 *
 * @return void
 */
function wcsrb_init(): void {
    if ( class_exists( '\XWP\Error\Error_Handler' ) && 'production' !== wp_get_environment_type() ) {
        ( new \XWP\Error\Error_Handler() )->register();
    }

    \XWP\DI\App_Factory::create(
        array(
            'compile'     => false,
            'compile_dir' => __DIR__ . '/cache',
            'id'          => 'wcsrb',
            'module'      => \Oblak\WCSRB\App::class,
        ),
    );
}

/**
 * Main Plugin Instance
 *
 * @return Oblak\WCSRB\App
 */
function WCSRB() {
    return xwp_app( 'wcsrb' )->get( Oblak\WCSRB\App::class );
}

/**
 * Get the saved bank accounts.
 *
 * @return array<int, string>
 */
function wcsrb_get_bank_accounts(): array {
    $accounts = \get_option( 'woocommerce_store_bank_accounts', array() );

    return wc_string_to_array( $accounts['acct'] ?? $accounts );
}

/**
 * Format a bank account number
 *
 * @param  string $acct   The account number.
 * @param  string $format The format to use. Short or long.
 * @param  string $sep    The separator to use.
 * @return string
 */
function wcsrb_format_bank_acct( string $acct, string $format = 'short', string $sep = '-' ): string {
    $acct   = str_replace( '-', '', $acct );
    $middle = ltrim( substr( $acct, 3, -2 ), '0' );

    if ( 'short' !== $format ) {
        $middle = str_pad( $middle, 13, '0', STR_PAD_LEFT );
    }

    return sprintf( '%1$s%4$s%2$s%4$s%3$s', substr( $acct, 0, 3 ), $middle, substr( $acct, -2 ), $sep );
}
