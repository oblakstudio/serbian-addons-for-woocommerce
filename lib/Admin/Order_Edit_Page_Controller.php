<?php
/**
 * Order_Edit_Page_Controller class file.
 *
 * @package Serbian Addons for WooCommerce
 */

namespace Oblak\WCSRB\Admin;

use Automattic\WooCommerce\Utilities\OrderUtil;
use Oblak\WCSRB\Services\Payments;
use Oblak\WCSRB\Services\QR_Code_Manager;
use WC_Order;
use WP_Post;
use XWP\DI\Decorators\Action;
use XWP\DI\Decorators\Filter;
use XWP\DI\Decorators\Handler;

/**
 * Filters and hooks for the Order Edit page
 *
 * @since 3.8.0
 */
#[Handler(
	tag: 'admin_init',
	priority: 99,
	context: Handler::CTX_ADMIN | Handler::CTX_AJAX,
	container: 'wcsrb',
)]
class Order_Edit_Page_Controller {
    /**
     * Constructor
     *
     * @param QR_Code_Manager $qrc      QR code manager instance.
     * @param Payments        $payments Payments utility instance.
     */
    public function __construct(
        private QR_Code_Manager $qrc,
        private Payments $payments,
    ) {
    }

    /**
     * Ajax action to view the IPS QR code
     */
    #[Action( tag: 'wp_ajax_wcsrb_view_ips_qr_code' )]
    public function view_nbs_ips_qr_code() {
        \check_ajax_referer( 'wcsrb-view-ips-qr-code', 'security' );

        $privs = \current_user_can( 'manage_woocommerce' );
        $order = \wc_get_order( (int) \xwp_fetch_get_var( 'order_id', '0' ) );
        $file  = $this->qrc->get_filename( $order );
        $data  = $this->qrc->read( $order, 'raw' );

        if ( ! $privs || ! $order || ! $data ) {
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

    /**
     * Adds the IPS Regeneration action to the order actions metabox
     *
     * @param  array    $actions Order actions.
     * @param  WC_Order $order   Order object.
     * @return array
     */
    #[Filter( tag: 'woocommerce_order_actions' )]
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
        $types = \array_merge(
            \wc_get_order_types( 'order-meta-boxes' ),
            array( 'woocommerce_page_wc-orders' ),
        );

        foreach ( $types as $type ) {
            \add_meta_box(
                'wcsrb-ips-qr-code',
                \__( 'IPS QR Code', 'serbian-addons-for-woocommerce' ),
                array( $this, 'qrcode_metabox' ),
                $type,
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

        if ( ! $this->qrc->has_qrcode( $theorder ) ) {
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
                \wp_json_encode( array( 's' => $this->payments->get_qr_string( $theorder ) ) ),
            ),
            \esc_html__( 'Copy IPS QR string', 'serbian-addons-for-woocommerce' ),
            \esc_html__( 'Copied!', 'serbian-addons-for-woocommerce' ),
        );
    }
}
