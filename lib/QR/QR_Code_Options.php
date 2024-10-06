<?php //phpcs:disable SlevomatCodingStandard.Attributes.AttributeAndTargetSpacing
/**
 * QR_Code_Options class file.
 *
 * @package Serbian Addons for WooCommerce
 */

namespace Oblak\WCSRB\QR;

use chillerlan\QRCode\Common\EccLevel;
use chillerlan\QRCode\Data\QRMatrix;
use chillerlan\QRCode\Output\QROutputInterface;
use chillerlan\QRCode\QROptions;
use DI\Attribute\Inject;
use Oblak\WCSRB\Gateway\Gateway_Payment_Slip;

/**
 * QR Code options with logo support.
 */
class QR_Code_Options extends QROptions {
    /**
     * Logo file path
     *
     * @var string|null
     */
    protected ?string $logo = null;

    /**
     * Desired format
     *
     * @var string
     */
    protected string $format = 'jpeg';

    /**
     * Constructor
     *
     * @param class-string         $generator QR code generator.
     * @param Gateway_Payment_Slip $gateway   Payment slip gateway.
     */
    public function __construct(
        #[Inject( 'ips.generator' )] string $generator,
        Gateway_Payment_Slip $gateway,
	) {
        parent::__construct(
            $this->parse_opts( $gateway->get_options(), $generator ),
        );
    }

    /**
     * Standardize the options
     *
     * @param  array<string,mixed> $opts Options.
     * @param class-string        $generator QR code generator.
     * @return array
     */
    private function parse_opts( array $opts, string $generator ): array {
        $isgd = QR_Generator_GD::class === $generator;

        $logo = $opts['qrcode_image'] ? \get_option( 'site_icon', 0 ) : 0;
        $qrdc = $isgd ? $this->hex2rgb( $opts['qrcode_color'] ) : $opts['qrcode_color'];
        $qrcc = $isgd ? $this->hex2rgb( $opts['qrcode_corner_color'] ) : $opts['qrcode_corner_color'];

        $args = array(
            'eccLevel'        => EccLevel::L,
            'moduleValues'    => array(
                QRMatrix::M_ALIGNMENT      => $qrdc,
                // Aligment.
                QRMatrix::M_ALIGNMENT_DARK => $qrdc,
                // Dark module.
                QRMatrix::M_DARKMODULE     => $qrdc,
                QRMatrix::M_DATA           => $qrdc,
                // Data.
                QRMatrix::M_DATA_DARK      => $qrdc,
                QRMatrix::M_FINDER         => $qrcc,
                // Finder.
                QRMatrix::M_FINDER_DARK    => $qrcc,
                QRMatrix::M_FINDER_DOT     => $qrcc,
                QRMatrix::M_FORMAT         => $qrdc,
                // Format.
                QRMatrix::M_FORMAT_DARK    => $qrdc,
                QRMatrix::M_TIMING         => $qrdc,
                // Timing.
                QRMatrix::M_TIMING_DARK    => $qrdc,
                QRMatrix::M_VERSION        => $qrdc,
                // Version.
                QRMatrix::M_VERSION_DARK   => $qrdc,
            ),
            'outputInterface' => $generator,
            'quality'         => 50,
            'scale'           => 15,
        );

        if ( $logo ) {
            $args = \array_merge(
                $args,
                array(
                    'addLogoSpace'    => true,
                    'eccLevel'        => EccLevel::H,
                    'logo'            => \get_attached_file( $logo ),
                    'logoSpaceHeight' => 20,
                    'logoSpaceWidth'  => 20,
                ),
            );
        }

        $args = \wp_parse_args(
            $args,
            array(
                'addQuietzone'     => true,
                'bgColor'          => '#fff',
                'drawLightModules' => false,
                'format'           => 'jpeg',
                'imageTransparent' => false,
                'imagickFormat'    => 'jpeg',
                'keepAsSquare'     => array(
                    QRMatrix::M_FINDER_DARK,
                    QRMatrix::M_FINDER_DOT,
                    QRMatrix::M_ALIGNMENT_DARK,
                ),
                'outputBase64'     => false,
                'outputType'       => QROutputInterface::CUSTOM,
                'quietzoneSize'    => 1,
                'scale'            => 5,
            ),
        );

        return $args;
    }

    /**
     * Converts a hex color to RGB.
     *
     * @param  string $hex Hex color.
     * @return array       RGB color.
     */
    private function hex2rgb( string $hex ): array {
        $hex = \hexdec( \ltrim( $hex, '#' ) );

        $r = ( $hex >> 16 ) & 0xFF;
        $g = ( $hex >> 8 ) & 0xFF;
        $b = $hex & 0xFF;

        return array( $r, $g, $b );
    }

    /**
     * Set the logo file path
     *
     * @param  string $logo Logo file path.
     *
     * @throws \chillerlan\QRCode\QRCodeException If the logo file is invalid.
     */
    protected function set_logo( ?string $logo = null ): void {
        if ( ! $logo ) {
            return;
        }

        if ( ! \file_exists( $logo ) || ! \is_file( $logo ) || ! \is_readable( $logo ) ) {
			throw new \chillerlan\QRCode\QRCodeException( 'Invalid logo file' );
		}

		$this->logo = $logo;
    }
}
