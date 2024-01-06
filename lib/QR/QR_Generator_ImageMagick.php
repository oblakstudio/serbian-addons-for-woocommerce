<?php //phpcs:disable Squiz.Commenting.FunctionComment.MissingParamTag
/**
 * QR_Generator_ImageMagick class file.
 *
 * @package Serbian Addons for WooCommerce
 */

namespace Oblak\WooCommerce\Serbian_Addons\QR;

use chillerlan\QRCode\Output\QRImagick;
use Imagick;

/**
 * Uses ImageMagick to generate QR Codes.
 */
class QR_Generator_ImageMagick extends QRImagick implements QR_Generator_Interface {

    /**
     * {@inheritDoc}
     */
    public static function supports_format( string $format ): bool {
        $mime_type         = wp_check_filetype( "a.{$format}" )['type'] ?? '';
        $imagick_extension = strtoupper( wp_get_default_extension_for_mime_type( $mime_type ) );

        if ( 'svg' === $format || ! $imagick_extension || ! method_exists( 'Imagick', 'setIteratorIndex' ) && 'image/jpeg' !== $mime_type ) {
			return false;
		}

        try {
			// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
			return ( (bool) @\Imagick::queryFormats( $imagick_extension ) );
		} catch ( \Exception $e ) {
			return false;
		}
    }

    /**
     * {@inheritDoc}
     */
    public static function test( array $args = array() ): bool {
        if ( ! extension_loaded( 'imagick' ) || ! class_exists( 'Imagick', false ) || ! class_exists( 'ImagickPixel', false ) ) {
			return false;
		}

		if ( version_compare( phpversion( 'imagick' ), '2.2.0', '<' ) ) {
			return false;
		}

        $required_methods = array(
			'clear',
			'destroy',
			'valid',
			'getimage',
			'writeimage',
			'getimageblob',
			'getimagegeometry',
			'getimageformat',
			'setimageformat',
			'setimagecompression',
			'setimagecompressionquality',
			'setimagepage',
			'setoption',
			'scaleimage',
			'cropimage',
			'rotateimage',
			'flipimage',
			'flopimage',
			'readimage',
			'readimageblob',
		);

		// Now, test for deep requirements within Imagick.
		if ( ! defined( 'imagick::COMPRESSION_JPEG' ) ) {
			return false;
		}

		$class_methods = array_map( 'strtolower', get_class_methods( 'Imagick' ) );
		if ( array_diff( $required_methods, $class_methods ) ) {
			return false;
		}

		return true;
    }

    /**
     * {@inheritDoc}
     */
    public function dump( ?string $file = null ) {
        $this->options->returnResource = true;

        parent::dump( $file );

        if ( ! $this->options->logo ) {
            $image = $this->imagick->getImageBlob();
            $this->imagick->destroy();
            $this->saveToFile( $image, $file );

            return $image;
        }

        $size = ( ( $this->options->logoSpaceWidth - 2 ) * $this->options->scale );
        $pos  = ( ( $this->moduleCount * $this->options->scale - $size ) / 2 ); //phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase

        $logo = new Imagick( $this->options->logo );
		$logo->resizeImage( $size, $size, Imagick::FILTER_LANCZOS, 0.85, true );

        $this->imagick->compositeImage( $logo, Imagick::COMPOSITE_ATOP, $pos, $pos );

        $image = $this->imagick->getImageBlob();

        $this->imagick->destroy();
		$this->saveToFile( $image, $file );

        return $image;
    }
}
