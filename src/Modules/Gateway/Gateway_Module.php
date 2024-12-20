<?php
/**
 * Gateway_Module class file.
 *
 * @package Serbian Addons for WooCommerce
 * @subpackage Gateway
 */

namespace Oblak\WCSRB\Gateway;

use Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry;
use chillerlan\QRCode\QRCode;
use Oblak\Lib\QR;
use XWP\DI\Decorators\Action;
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
            'ips.dir'     => \DI\factory(
                static fn() => \defined( 'WCRS_IPS_DIR' )
                    ? WCRS_IPS_DIR
                    : \wp_upload_dir()['basedir'] . '/wcrs-ips',
            ),
            'ips.gen'     => \DI\factory(
                static fn() => \class_exists( \Imagick::class )
                    ? QR\QR_Generator_ImageMagick::class
                    : QR\QR_Generator_GD::class,
            ),
            'ips.opts'    => \DI\factory(
                static fn( Gateway_Payment_Slip $gw ) => $gw->get_options(),
            ),
            QRCode::class => \DI\factory(
                static fn( QR\QR_Generator_Options $opts ) => new QRCode( $opts ),
            ),
        );
    }
    /**
     * Adds our Payment Gateway to list of WooCommerce Gateways
     *
     * @template TGw of \WC_Payment_Gateway
     * @param  array<int,class-string<TGw>|TGw> $gateways List of gateways.
     * @param  Gateway_Payment_Slip             $gw       Payment Slip Gateway.
     * @return array<int,class-string<TGw>|TGw>           Modified list of gateways.
     */
    #[Filter(
        tag: 'woocommerce_payment_gateways',
        priority: 50,
        invoke: Filter::INV_PROXIED,
        args: 1,
        params: array( Gateway_Payment_Slip::class ),
    )]
    public function add_payment_gateways( array $gateways, Gateway_Payment_Slip $gw ) {
        $gateways[] = $gw;

        return $gateways;
    }

    /**
     * Registers our Payment Gateway Block
     *
     * @param  PaymentMethodRegistry      $reg Payment Method Registry.
     * @param  Gateway_Payment_Slip_Block $gw  Payment Slip Gateway Block.
     */
    #[Action(
        tag: 'woocommerce_blocks_payment_method_type_registration',
        priority: 10,
        invoke: Filter::INV_PROXIED,
        args: 1,
        params: array( Gateway_Payment_Slip_Block::class ),
    )]
    public function add_block_gateway( PaymentMethodRegistry $reg, Gateway_Payment_Slip_Block $gw ): void {
        $reg->register( $gw );
    }
}
