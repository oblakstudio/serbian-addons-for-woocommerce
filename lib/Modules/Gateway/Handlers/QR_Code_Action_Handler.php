<?php
/**
 * QR_Code_Action_Handler class file.
 *
 * @package Serbian Addons for WooCommerce
 * @subpackage Gateway
 */

namespace Oblak\WCSRB\Gateway\Handlers;

use Oblak\WCSRB\Gateway\Services\QR_Code_Manager;
use XWP\DI\Decorators\Ajax_Action;
use XWP\DI\Decorators\Ajax_Handler;

/**
 * Handles the QR code ajax actions
 *
 * @since 4.0.0
 */
#[Ajax_Handler( container: 'wcsrb', priority: 10 )]
class QR_Code_Action_Handler {
    /**
     * Ajax action to view the IPS QR code
     *
     * @param  int             $order_id The order ID.
     * @param  QR_Code_Manager $qrc      The QR code manager. Injected by the container.
     */
    #[Ajax_Action(
        prefix: 'wcsrb',
        action: 'view_ips_qr_code',
        public: false,
        method: Ajax_Action::AJAX_GET,
        nonce: 'security',
        cap: 'manage_woocommerce',
        vars: array( 'order_id' => 0 ),
        params: array( QR_Code_Manager::class ),
    )]
    public function view_nbs_ips_qr_code( int $order_id, QR_Code_Manager $qrc ) {
        $order = \wc_get_order( $order_id );
        $file  = $qrc->get_filename( $order );
        $data  = $qrc->read( $order, 'raw' );

        if ( ! $order || ! $data ) {
            exit;
        }

        \wc_nocache_headers();
        \header( "Content-Disposition: inline; filename=\"ips-qr-order-{$order->get_id()}\"" );
        \header( 'Content-Length: ' . \xwp_wpfs()->size( $file ) );
        \header( 'Content-Type: image/jpeg' );
        \header( 'Date: ' . \gmdate( 'D, d M Y H:i:s T', \xwp_wpfs()->mtime( $file ) ) );

        echo $data; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

        exit;
    }
}
