<?php
/**
 * Order_Edit_Page_Controller class file.
 *
 * @package Serbian Addons for WooCommerce
 */

namespace Oblak\WCSRB\Gateway\Handlers;

use Automattic\WooCommerce\Utilities\OrderUtil;
use DI\Container;
use Oblak\WCSRB\Gateway\Gateway_Payment_Slip;
use Oblak\WCSRB\Gateway\Services\QR_Code_Metabox;
use WC_Order;
use XWP\DI\Decorators\Action;
use XWP\DI\Decorators\Filter;
use XWP\DI\Decorators\Handler;

/**
 * Filters and hooks for the Order Edit page
 *
 * @since 3.8.0
 */
#[Handler(
    tag: 'current_screen',
    priority: 10,
    context: Handler::CTX_ADMIN,
    strategy: Handler::INIT_JUST_IN_TIME,
    container: 'wcsrb',
)]
class Order_Edit_Page_Handler {
    /**
     * Check if the handler can be initialized.
     *
     * @return bool
     */
    public static function can_initialize(): bool {
        $screen_id = OrderUtil::custom_orders_table_usage_is_enabled()
            ? \wc_get_page_screen_id( 'shop-order' )
            : 'shop_order';

        return OrderUtil::is_order_edit_screen() && \get_current_screen()->id === $screen_id;
    }

    /**
     * Adds the IPS Regeneration action to the order actions metabox
     *
     * @param  array<string,string> $actions Order actions.
     * @param  WC_Order             $order   Order object.
     * @param  Gateway_Payment_Slip $gw      Payment slip gateway. Injected by the container.
     * @return array<string,string>
     */
    #[Filter(
        tag: 'woocommerce_order_actions',
        priority: 10,
        invoke: Filter::INV_PROXIED,
        args: 2,
        params: array( Gateway_Payment_Slip::class ),
    )]
    public function add_action_to_metabox( array $actions, WC_Order $order, Gateway_Payment_Slip $gw ): array {
        if ( $gw->slip_enabled( $order, true ) ) {
            $actions['wcsrb_gen_ips'] = \__( 'Regenerate IPS QR code', 'serbian-addons-for-woocommerce' );
        }

        return $actions;
    }

    /**
     * Add the invoice metabox to the order edit page.
     *
     * @param  string    $screen_id The screen ID.
     * @param  WC_Order  $order     The order object.
     * @param  Container $cnt       The DI container.
     */
    #[Action(
        tag: 'add_meta_boxes',
        priority: 10,
        invoke:Action::INV_PROXIED,
        args: 2,
        params: array( Container::class ),
    )]
    public function add_meta_box(
        string $screen_id,
        WC_Order $order,
        Container $cnt,
    ): void {
        \add_meta_box(
            id: 'wcsrb-ips-qr-code',
            title: \__( 'IPS QR Code', 'serbian-addons-for-woocommerce' ),
            callback: static fn( $o ) => $cnt->call(
                array( QR_Code_Metabox::class, 'render' ),
                array( 'order' => $o ),
            ),
            screen: $screen_id,
            context: 'side',
            callback_args: array( 'order' => $order ),
        );
    }
}
