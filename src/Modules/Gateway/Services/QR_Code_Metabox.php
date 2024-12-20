<?php
/**
 * QR_Code_Metabox class file.
 *
 * @package Serbian Addons for WooCommerce
 * @subpackage Gateway
 */

namespace Oblak\WCSRB\Gateway\Services;

use WC_Order;

/**
 * IPS QR Code metabox.
 *
 * @since 4.0.0
 */
class QR_Code_Metabox {
    /**
     * Constructor
     *
     * @param QR_Code_Manager $qrc      QR code manager instance.
     */
    public function __construct(
        private QR_Code_Manager $qrc,
    ) {
    }

    /**
     * Render the metabox.
     *
     * @param WC_Order $order The order.
     */
    public function render( WC_Order $order ): void {
        if ( ! \wcsrb_order_has_slip( $order ) ) {
            \printf(
                '<p>%s</p>',
                \esc_html__( 'No IPS QR code available for this order.', 'serbian-addons-for-woocommerce' ),
            );
            return;
        }

        \printf(
            <<<'HTML'
            <img src="%s" style="max-width: 100%%; height: auto; margin-bottom: 10px;">
            HTML,
            \esc_url(
                \add_query_arg(
                    array(
                        'action'   => 'wcsrb_view_ips_qr_code',
                        'order_id' => $order->get_id(),
                        'security' => \wp_create_nonce( 'wcsrb_view_ips_qr_code' ),
                    ),
                    \admin_url( 'admin-ajax.php' ),
                ),
            ),
        );

        \printf(
            <<<'HTML'
            <button data-ips="%s" class="button button-secondary wcsrb-copy-ips-qr">%s</button>
            <span class="ips-qr-copy-success" style="display:none">%s</span>
            HTML,
            \wc_esc_json(
                \wp_json_encode( array( 's' => $this->qrc->get_qr_string( $order ) ) ),
            ),
            \esc_html__( 'Copy IPS QR string', 'serbian-addons-for-woocommerce' ),
            \esc_html__( 'Copied!', 'serbian-addons-for-woocommerce' ),
        );
    }
}
