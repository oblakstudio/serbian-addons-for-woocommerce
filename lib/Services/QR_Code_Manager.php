<?php //phpcs:disable WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
/**
 * Field_Validator class file.
 *
 * @package Serbian Addons for WooCommerce
 */

namespace Oblak\WCSRB\Services;

use chillerlan\QRCode\QRCode;
use DI\Attribute\Inject;
use Oblak\WCSRB\Services\Payments;
use WC_Order;

/**
 * Manages the QR Codes.
 */
class QR_Code_Manager {
    /**
     * Constructor
     *
     * @param string   $basedir  The base directory.
     * @param Payments $payments The payments service.
     */
    public function __construct(
        #[Inject( 'ips.basedir' )]
        private string $basedir,
        private Payments $payments,
    ) {
    }

    /**
     * Checks if the order has a QR code.
     *
     * @param  WC_Order $order The order object.
     * @return bool
     */
    public function has_qrcode( WC_Order $order ): bool {
        return \wcsrb_order_has_slip( $order ) && \xwp_wpfs()->exists( $this->get_filename( $order ) );
    }

    /**
     * Creates a QR Code image for the order.
     *
     * @param  WC_Order $order  The order object.
     * @param  string   $format The image format.
     * @return bool
     */
    public function create( WC_Order $order, string $format = 'jpg' ): bool {
        return $this->save(
            $this->get_filename( $order, $format ),
            $this->make( $this->payments->get_qr_string( $order ) ),
        );
    }

    /**
     * Reads the QR Code image for the order.
     *
     * @param  WC_Order $order  The order object.
     * @param  string   $format Data format. Can be 'base64' or 'raw'.
     * @return string
     */
    public function read( WC_Order $order, string $format = 'base64' ): string {
        if ( ! $this->has_qrcode( $order ) ) {
            return '';
        }

        $data = \xwp_wpfs()->get_contents( $this->get_filename( $order ) );

        if ( ! $data ) {
            return '';
        }

        return 'base64' === $format
            ? 'data:image/jpg;base64, ' . \base64_encode( $data )
            : $data;
    }

    /**
     * Deletes the QR Code image for the order.
     *
     * @param  WC_Order $order The order object.
     * @return bool
     */
    public function delete( WC_Order $order ): bool {
        $filename = $this->get_filename( $order );

        if ( ! $filename || ! \xwp_wpfs()->exists( $filename ) ) {
            return false;
        }

        return \xwp_wpfs()->delete( $filename );
    }

    /**
     * Get template arguments for the QR Code
     *
     * @param  WC_Order $order The order object.
     * @param  string   $type  The template type. Can be 'display' or 'email'.
     * @return array
     */
    public function get_template_args( WC_Order $order, string $type ): array {
        return array(
            'alt'  => \__( 'IPS QR Code', 'serbian-addons-for-woocommerce' ),
            'path' => $this->get_filename( $order ),
            'src'  => 'email' !== $type
                ? $this->read( $order, 'base64' )
                : 'cid:ips-qr-code',

        );
    }

    /**
     * Makes a QR Code image.
     *
     * @param  string $data The data to encode.
     * @return string
     */
    protected function make( string $data ) {
        return \xwp_app( 'wcsrb' )->call( array( QRCode::class, 'render' ), array( $data ) );
    }

    /**
     * Saves the QR Code image.
     *
     * @param  string $filename The file name.
     * @param  string $data     The data to save.
     * @return bool
     */
    protected function save( string $filename, string $data ) {
        return \xwp_wpfs()->put_contents( $filename, $data );
    }

    /**
     * Gets the QR Code file name.
     *
     * @param  null|false|WC_Order $order        Order object.
     * @param  string              $format       File format.
     * @param  bool                $with_basedir Whether to include the base directory.
     * @return string                 The file name.
     */
    public function get_filename( null|bool|WC_Order $order, string $format = 'jpg', bool $with_basedir = true ): string {
        $order = ! ( $order instanceof WC_Order ) ? \wc_get_order( $order ?? false ) : $order;

        return $order ? \sprintf(
            '%4$s%1$s-%2$s.%3$s',
            $order->get_id(),
            $order->get_order_key(),
            $format,
            $with_basedir ? \trailingslashit( $this->basedir ) : '',
        ) : '';
    }
}
