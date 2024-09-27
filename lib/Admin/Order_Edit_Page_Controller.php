<?php
/**
 * Order_Edit_Page_Controller class file.
 *
 * @package Serbian Addons for WooCommerce
 */

namespace Oblak\WCSRB\Admin;

use Automattic\WooCommerce\Utilities\OrderUtil;
use Oblak\WooCommerce\Serbian_Addons\QR\QR_Code_Handler;
use Oblak\WP\Abstracts\Hook_Caller;
use Oblak\WP\Decorators\Action;
use Oblak\WP\Decorators\Hookable;
use WC_Order;
use WP_Post;

/**
 * Filters and hooks for the Order Edit page
 *
 * @since 3.8.0
 */
#[Hookable( 'admin_init' )]
class Order_Edit_Page_Controller extends Hook_Caller {
    /**
     * Ajax action to view the IPS QR code
     */
    #[Action( tag: 'wp_ajax_wcsrb_view_ips_qr_code' )]
    public function view_nbs_ips_qr_code() {
        \check_ajax_referer( 'wcsrb-view-ips-qr-code', 'security' );

        $privs = \current_user_can( 'manage_woocommerce' );
        $order = \wc_get_order( (int) \xwp_fetch_get_var( 'order_id', '0' ) );
        $file  = QR_Code_Handler::get_filename( $order );

        if ( ! $privs || ! $order || ! $file || ! \xwp_wpfs()->exists( $file ) ) {
            exit;
        }

        \wc_nocache_headers();
        \header( "Content-Disposition: inline; filename=\"ips-qr-order-{$order->get_id()}\"" );
        \header( 'Content-Length: ' . \xwp_wpfs()->size( $file ) );
        \header( 'Content-Type: image/jpeg' );
        \header( 'Date: ' . \gmdate( 'D, d M Y H:i:s T', \xwp_wpfs()->mtime( $file ) ) );

        echo \xwp_wpfs()->get_contents( $file ); // phpcs:ignore

        exit;
    }

    /**
     * Adds the IPS Regeneration action to the order actions metabox
     *
     * @param  array    $actions Order actions.
     * @param  WC_Order $order   Order object.
     * @return array
     */
    #[Action( tag: 'woocommerce_order_actions' )]
    public function add_action_to_metabox( array $actions, WC_Order $order ): array {
        if ( \wcsrb_order_has_slip( $order, true ) ) {
            $actions['wcsrb_gen_ips'] = \__( 'Regenerate IPS QR code', 'serbian-addons-for-woocommerce' );
        }

        return $actions;
    }

    /**
     * Adds the IPS QR code metabox to the order view page
     */
    #[Action( tag: 'add_meta_boxes' )]
    public function add_ips_qr_metabox() {
		$screen_id = \get_current_screen()?->id ?? '';

        foreach ( \wc_get_order_types( 'order-meta-boxes' ) as $type ) {
            \add_meta_box(
                'wcsrb-ips-qr-code',
                \__( 'IPS QR Code', 'serbian-addons-for-woocommerce' ),
                array( $this, 'qrcode_metabox' ),
                $screen_id,
                'side',
            );
        }
    }

    /**
     * Displays the IPS QR code metabox
     *
     * @param WC_Order|WP_Post $post Order object.
     */
    public function qrcode_metabox( $post ) {
        global $theorder;

        // @phpstan-ignore argument.type
        OrderUtil::init_theorder_object( $post );

        if ( ! \wcsrb_order_has_qrcode( $theorder ) ) {
            return \printf(
                '<p>%s</p>',
                \esc_html__( 'No IPS QR code available for this order.', 'serbian-addons-for-woocommerce' ),
            );
        }

        \printf(
            <<<'HTML'
            <img src="%s" style="max-width: 100%%; height: auto; margin-bottom: 10px;">
            HTML,
            \esc_url(
                \add_query_arg(
                    array(
                        'action'   => 'wcsrb_view_ips_qr_code',
                        'order_id' => $theorder->get_id(),
                        'security' => \wp_create_nonce( 'wcsrb-view-ips-qr-code' ),
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
                \wp_json_encode( array( 's' => \WCSRB()->payments()->get_qr_string( $theorder ) ) ),
            ),
            \esc_html__( 'Copy IPS QR string', 'serbian-addons-for-woocommerce' ),
            \esc_html__( 'Copied!', 'serbian-addons-for-woocommerce' ),
        );
    }
}
