<?php //phpcs:disable Squiz.Commenting.FunctionComment
/**
 * QR_Generator_ImageMagick class file.
 *
 * @package Serbian Addons for WooCommerce
 */

namespace Oblak\WCSRB\QR;

use chillerlan\QRCode\Output\QRImagick;
use Imagick;

/**
 * Uses ImageMagick to generate QR Codes.
 */
class QR_Generator_ImageMagick extends QRImagick {
    public function dump( ?string $file = null ) {
        $this->options->returnResource = true;

        parent::dump( $file );

        if ( ! $this->options->logo ) {

            $image = $this->imagick->getImageBlob();
            $this->imagick->destroy();
            $this->saveToFile( $image, $file );

            return $image;
        }

        $size = ( $this->options->logoSpaceWidth - 2 ) * $this->options->scale;
        $pos  = ( $this->moduleCount * $this->options->scale - $size ) / 2; //phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase

        $logo = new Imagick( $this->options->logo );
		$logo->resizeImage( $size, $size, Imagick::FILTER_LANCZOS, 0.85, true );

        $this->imagick->compositeImage( $logo, Imagick::COMPOSITE_ATOP, $pos, $pos );

        $image = $this->imagick->getImageBlob();

        $this->imagick->destroy();
		$this->saveToFile( $image, $file );

        return $image;
    }
}
