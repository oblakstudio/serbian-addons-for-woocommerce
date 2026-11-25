<?php
/**
 * Fixtures class file.
 *
 * @package Serbian Addons for WooCommerce
 * @subpackage Tests
 */

namespace Oblak\WCSRB\Tests\Support;

/**
 * Static test data. All identifiers below pass the oblak/serbian-validators checks.
 */
final class Fixtures {
    /**
     * Valid bank account (long format).
     */
    public const BANK_ACCOUNT = '160-0000000123456-54';

    /**
     * Same account in the short format the settings page stores.
     */
    public const BANK_ACCOUNT_SHORT = '160-123456-54';

    /**
     * Valid company number (maticni broj).
     */
    public const MB = '10000009';

    /**
     * Valid tax number (PIB).
     */
    public const PIB = '100000008';

    /**
     * Baseline payment slip gateway settings: enabled, slip + QR shown everywhere.
     *
     * @return array<string,mixed>
     */
    public static function gateway_settings(): array {
        return array(
            'enabled'             => 'yes',
            'title'               => 'Payment Slip',
            'description'         => 'Pay by sending us money via wire transfer',
            'display'             => array( 'order', 'email' ),
            'style'               => 'modern',
            'bank_account'        => self::BANK_ACCOUNT_SHORT,
            'payment_code'        => 'auto',
            'payment_model'       => 'auto',
            'payment_reference'   => '%order_id%-%year%',
            'payment_purpose'     => 'Order payment',
            'qrcode_shown'        => array( 'order', 'email' ),
            'qrcode_color'        => '#000000',
            'qrcode_corner_color' => '#000000',
            'qrcode_image'        => 'no',
            'debug'               => 'no',
        );
    }

    /**
     * Baseline store options: a Serbian store selling in RSD with one bank account.
     *
     * @return array<string,mixed>
     */
    public static function store_options(): array {
        return array(
            'woocommerce_currency'                    => 'RSD',
            'woocommerce_default_country'             => 'RS',
            'woocommerce_store_address'               => 'Knez Mihailova 1',
            'woocommerce_store_address_2'             => '',
            'woocommerce_store_bank_accounts'         => array( self::BANK_ACCOUNT_SHORT ),
            'woocommerce_store_city'                  => 'Beograd',
            'woocommerce_store_name'                  => 'Test Prodavnica d.o.o.',
            'woocommerce_store_postcode'              => '11000',
            'woocommerce_wcsrb_payment_slip_settings' => self::gateway_settings(),
        );
    }
}
