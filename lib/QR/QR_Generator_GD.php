<?php //phpcs:disable Squiz.Commenting.FunctionComment
/**
 * QR_Generator_GD class file.
 *
 * @package Serbian Addons for WooCommerce
 */

namespace Oblak\WCSRB\QR;

use chillerlan\QRCode\Output\QRGdImageJPEG;

/**
 * Uses GD to generate QR Codes.
 */
class QR_Generator_GD extends QRGdImageJPEG {
    public function dump( ?string $file = null ) {
        $this->options->returnResource = true;

        parent::dump( $file );

        if ( ! $this->options->logo ) {
            $image = $this->dumpImage();

            $this->saveToFile( $image, $file );

            return $image;
        }

        $image = \imagecreatefromstring( \xwp_wpfs()->get_contents( $this->options->logo ) );

        $w = \imagesx( $image );
        $h = \imagesy( $image );

        $lw = ( $this->options->logoSpaceWidth - 2 ) * $this->options->scale;
		$lh = ( $this->options->logoSpaceHeight - 2 ) * $this->options->scale;
        $ql = $this->matrix->getSize() * $this->options->scale;

        \imagecopyresampled(
            $this->image,
            $image,
            ( $ql - $lw ) / 2,
            ( $ql - $lh ) / 2,
            0,
            0,
            $lw,
            $lh,
            $w,
            $h,
        );

        $image = $this->dumpImage();

        $this->saveToFile( $image, $file );

        return $image;
    }

    protected function renderImage(): void {
        match ( $this->options->format ) {
            'bmp' => \imagebmp( $this->image, null, ( $this->options->quality > 0 ) ),
            'gif' => \imagegif( $this->image ),
            'jpg', 'jpeg' => \imagejpeg( $this->image, null, \max( -1, \min( 100, $this->options->quality ) ) ),
            'webp' => \imagewebp( $this->image, null, \max( 0, \min( 100, $this->options->quality ) ) ),
            'png' => \imagepng(
                $this->image,
                null,
                \max( 0, \min( 9, (int) \round( $this->options->quality / 10 ) ) ),
            ),
            default => \imagepng(
                $this->image,
                null,
                \max( 0, \min( 9, (int) \round( $this->options->quality / 10 ) ) ),
            ),
        };
    }
}
