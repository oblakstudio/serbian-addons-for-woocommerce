<?php
/**
 * Gateway_Module class file.
 *
 * @package Serbian Addons for WooCommerce
 * @subpackage Gateway
 */

namespace Oblak\WCSRB\Gateway;

use chillerlan\QRCode\QRCode;
use XWP\DI\Decorators\Filter;
use XWP\DI\Decorators\Module;

/**
 * Payment Gateway Module
 *
 * @since 4.0.0
 */
#[Module(
    container: 'wcsrb',
    hook: 'woocommerce_loaded',
    priority: 1,
    handlers: array(
        Handlers\Gateway_Payment_Slip_IPS_Handler::class,
        Handlers\Order_Edit_Page_Handler::class,
        Handlers\QR_Code_Action_Handler::class,
    ),
)]
class Gateway_Module {
    /**
     * DI Definitions
     *
     * @return array<string,mixed>
     */
    public static function configure(): array {
        return array(
            'ips.basedir'   => \DI\factory(
                static fn() => \defined( 'WCRS_IPS_DIR' )
                    ? WCRS_IPS_DIR
                    : \wp_upload_dir()['basedir'] . '/wcrs-ips',
            ),
            'ips.generator' => \DI\factory(
                static fn() => \class_exists( \Imagick::class )
                    ? Services\QR_Generator_ImageMagick::class
                    : Services\QR_Generator_GD::class,
            ),
            QRCode::class   => \DI\factory(
                static fn( Services\QR_Code_Options $opts ) => new QRCode( $opts ),
            ),
        );
    }
    /**
     * Adds our Payment Gateway to list of WooCommerce Gateways
     *
     * @param  array<int,class-string<\WC_Payment_Gateway>|\WC_Payment_Gateway> $gateways List of gateways.
     * @param  Services\Gateway_Payment_Slip                                    $gw       Payment Slip Gateway.
     * @return array<int,class-string<\WC_Payment_Gateway>|\WC_Payment_Gateway>           Modified list of gateways.
     */
    #[Filter(
        tag: 'woocommerce_payment_gateways',
        priority: 50,
        invoke: Filter::INV_PROXIED,
        args: 1,
        params: array(
            Services\Gateway_Payment_Slip::class,
        ),
    )]
    public function add_payment_gateways( array $gateways, Services\Gateway_Payment_Slip $gw ) {
        $gateways[] = $gw;

        return $gateways;
    }
}
