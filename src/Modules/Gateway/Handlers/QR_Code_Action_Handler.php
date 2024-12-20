<?php //phpcs:disable Universal.Operators.DisallowShortTernary.Found
/**
 * QR_Code_Action_Handler class file.
 *
 * @package Serbian Addons for WooCommerce
 * @subpackage Gateway
 */

namespace Oblak\WCSRB\Gateway\Handlers;

use Oblak\WCSRB\Gateway\Services\QR_Code_Manager;
use WC_Cache_Helper;
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
        [ $mtime, $etag ] = $this->get_cached_data( $order_id );

        \header( 'Pragma: cache' );
        \header( 'Cache-Control: public, max-age=3600, must-revalidate' );
        \header( 'Vary: Accept-Encoding' );

        if ( $this->cache_valid( $mtime, $etag ) ) {
            \status_header( 304 );
            \header( 'X-Cache-Status: HIT' );
            exit;
        }

        $order = \wc_get_order( $order_id );
        $image = $qrc->get( $order );
        $mtime = $qrc->mtime( $order );
        $size  = \strlen( $image );
        $etag  = \md5( $image . $mtime );

        $this->set_cached_data( $order_id, $mtime, $etag );

        \header( 'Content-Type: image/jpeg' );
        \header( "Content-Disposition: inline; filename=\"ips-qr-order-{$order_id}.jpg\"" );
        \header( "Content-Length: {$size}" );
        \header( 'Last-Modified: ' . \gmdate( 'D, d M Y H:i:s T', $mtime ) );
        \header( "ETag: {$etag}" );
        \header( 'X-Cache-Status: MISS' );

        echo $image; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

        exit;
    }

    /**
     * Checks if the cache is valid
     *
     * @param  int    $mtime The modified time.
     * @param  string $etag  The etag.
     * @return bool
     */
    private function cache_valid( int $mtime, string $etag ): bool {
        return \xwp_fetch_server_var( 'HTTP_IF_MODIFIED_SINCE', '' ) === \gmdate(
            'D, d M Y H:i:s T',
            $mtime,
        ) &&
            \xwp_fetch_server_var( 'HTTP_IF_NONE_MATCH', '' ) === $etag;
    }

    /**
     * Gets the cached data for the order
     *
     * @param  int $order_id The order ID.
     * @return array{int, string}
     */
    private function get_cached_data( int $order_id ): array {
        $key  = WC_Cache_Helper::get_prefixed_key( 'order-' . $order_id, 'wcsrb-ips-qr' );
        $data = \wp_cache_get( $key, 'wcsrb-ips-qr' ) ?: null;

        return array(
            $data['time'] ?? \time(),
            $data['etag'] ?? '',
        );
    }

    /**
     * Sets the cached data for the order
     *
     * @param  int    $order_id The order ID.
     * @param  int    $time     The time.
     * @param  string $etag     The etag.
     */
    private function set_cached_data( int $order_id, int $time, string $etag ): void {
        \wp_cache_set(
            WC_Cache_Helper::get_prefixed_key( 'order-' . $order_id, 'wcsrb-ips-qr' ),
            array(
                'etag' => $etag,
                'time' => $time,
            ),
            'wcsrb-ips-qr',
            3600,
        );
    }
}
