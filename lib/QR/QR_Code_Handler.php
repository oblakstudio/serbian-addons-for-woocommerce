<?php
/**
 * QR_Code_Handler class file.
 *
 * @package Serbian Addons for WooCommerce
 */

namespace Oblak\WooCommerce\Serbian_Addons\QR;

use chillerlan\QRCode\Data\QRMatrix;
use chillerlan\QRCode\Output\QROutputInterface;
use chillerlan\QRCode\QRCode;
use WC_Order;
use XWP\Helper\Traits\Singleton;

/**
 * Handles the creation and rendering of QR Codes.
 */
class QR_Code_Handler {
    use Singleton;

    /**
     * QR Code generator.
     *
     * @var QRCode|null
     */
    protected ?QRCode $qr_gen = null;

    /**
     * QR Code base directory.
     *
     * @var string
     */
    protected string $basedir;

    /**
     * Is the QR Code handler initialized?
     *
     * @var bool
     */
    protected static bool $initialized = false;

    /**
     * Constructor.
     */
    protected function __construct() {
        $this->basedir = WCRS_IPS_DIR;
    }

    /**
     * Choose the best QR Code implementation.
     *
     * @return string|false
     */
    protected function choose_implementation(): string|false {
        if ( \class_exists( \Imagick::class ) ) {
            return QR_Generator_ImageMagick::class;
        }

        return QR_Generator_GD::class;
    }

    /**
     * Initializes the QR Code generator.
     *
     * @param  array $args QR Code arguments.
     * @return QRCode
     */
    protected function init_generator( array $args ): QRCode {
        $args = \wp_parse_args(
            $args,
            array(
                'addQuietzone'     => true,
                'bgColor'          => '#fff',
                'drawLightModules' => false,
                'imageTransparent' => false,
                'imagickFormat'    => $args['format'] ?? 'png',
                'keepAsSquare'     => array(
                    QRMatrix::M_FINDER_DARK,
                    QRMatrix::M_FINDER_DOT,
                    QRMatrix::M_ALIGNMENT_DARK,
                ),
                'outputBase64'     => false,
                'outputInterface'  => $this->choose_implementation(),
                'outputType'       => QROutputInterface::CUSTOM,
                'quietzoneSize'    => 1,
                'scale'            => 5,
            ),
        );

        if ( QR_Generator_GD::class === $args['outputInterface'] ) {
            $args['moduleValues'] = \array_map( array( $this, 'hex2rgb' ), $args['moduleValues'] );
        }

        /**
         * Filter the QR Code generator arguments.
         *
         * @param array $args QR Code generator arguments.
         * @return array
         *
         * @since 3.3.0
         */
        $args = \apply_filters( 'woocommerce_serbian_qr_code_generator_args', $args );

        self::$initialized = true;

        return new QrCode( new QR_Code_Options( $args ) );
    }

    /**
     * Converts a hex color to RGB.
     *
     * @param  string $hex Hex color.
     * @return array       RGB color.
     */
    public function hex2rgb( string $hex ): array {
        $hex = \hexdec( \ltrim( $hex, '#' ) );

        $r = ( $hex >> 16 ) & 0xFF;
        $g = ( $hex >> 8 ) & 0xFF;
        $b = $hex & 0xFF;

        return array( $r, $g, $b );
    }

    /**
     * Initializes the handler.
     *
     * Returns the handler instance, for chaining.
     *
     * @param  array $args QR Code arguments.
     * @return QR_Code_Handler
     */
    public function init( array $args ): QR_Code_Handler {
        if ( self::$initialized ) {
            return $this;
        }

        $this->qr_gen = $this->init_generator( $args );

        return $this;
    }

    /**
     * Renders the QR Code.
     *
     * @param  string     $ips_code IPS Code.
     * @param  array|null $args     QR Code arguments.
     */
    public function create_qr_code( string $ips_code, ?array $args = null ) {
        if ( ! self::$initialized || $args ) {
            self::$initialized = false;

            $this->init( $args );
        }

        return $this->qr_gen->render( $ips_code );
    }

    /**
     * Saves the QR Code to a file.
     *
     * @param  string $qr_code QR Code.
     * @param  string $file    File name.
     * @return bool
     */
    public function save_file( string $qr_code, string $file ): bool {
        return \wp_load_filesystem()->put_contents( $file, $qr_code );
    }

    /**
     * Creates the QR Code file.
     *
     * @param  WC_Order   $order  Order object.
     * @param  string     $format File format.
     * @param  array|null $args   QR Code arguments.
     * @return bool
     */
    public function create_file( WC_Order $order, string $format = 'jpg', ?array $args = null ): bool {
        $qr_code = $this->create_qr_code(
            $order->get_meta( '_payment_slip_ips_data', true ),
            $args,
        );

        return $this->save_file(
            $qr_code,
            $this->get_filename( $order, $format ),
        );
    }

    /**
     * Get the QR Code file data
     *
     * @param  WC_Order $order  Order object.
     * @param  string   $format File format.
     * @param  bool     $force  Whether to force the generation of the QR Code.
     * @return string|false         File data, or false if the file does not exist.
     */
    public function get_file( WC_Order $order, string $format = 'jpg', bool $force = false ): string|false {
        $filepath = $this->get_filename( $order, $format );

        if ( ! $force && \file_exists( $filepath ) ) {
            return \wp_load_filesystem()->get_contents( $filepath );
        }

        if ( ! $this->create_file( $order, $format ) ) {
            return false;
        }

        return \wp_load_filesystem()->get_contents( $filepath );
    }

    /**
     * Get the QR Code file data as base64
     *
     * @param  WC_Order $order  Order object.
     * @param  string   $format File format.
     * @param  bool     $force  Whether to force the generation of the QR Code.
     * @return string|false         File data, or false if the file does not exist.
     */
    public function get_file_base64( WC_Order $order, string $format = 'jpg', bool $force = false ): string|false {
        $filepath = $this->get_filename( $order, $format );
        $file     = null;

        if ( ! $force && \file_exists( $filepath ) ) {
            $file = \wp_load_filesystem()->get_contents( $filepath );
        }

        if ( ! $file ) {
            $this->create_file( $order, $format );
            $file = \wp_load_filesystem()->get_contents( $filepath );
        }

        return $file
            // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions
            ? 'data:image/jpg;base64, ' . \base64_encode( $file )
            : false;
    }

    /**
     * Gets the QR Code file name.
     *
     * @param  WC_Order $order        Order object.
     * @param  string   $format       File format.
     * @param  bool     $with_basedir Whether to include the base directory.
     * @return string                 The file name.
     */
    public function get_filename( WC_Order $order, string $format = 'jpg', bool $with_basedir = true ): string {
        return \sprintf(
            '%4$s%1$s-%2$s.%3$s',
            $order->get_id(),
            $order->get_order_key(),
            $format,
            $with_basedir ? \trailingslashit( $this->basedir ) : '',
        );
    }
}
