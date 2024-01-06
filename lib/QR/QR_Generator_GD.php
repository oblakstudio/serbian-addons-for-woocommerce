<?php //phpcs:disable Squiz.Commenting.FunctionComment.MissingParamTag
/**
 * QR_Generator_GD class file.
 *
 * @package Serbian Addons for WooCommerce
 */

namespace Oblak\WooCommerce\Serbian_Addons\QR;

use chillerlan\QRCode\Output\QRGdImage;

/**
 * Uses GD to generate QR Codes.
 */
class QR_Generator_GD extends QRGdImage implements QR_Generator_Interface {

    /**
     * {@inheritDoc}
     */
    public static function test( array $args ): bool {
        return extension_loaded( 'gd' ) || function_exists( 'gd_info' );
    }

    /**
     * {@inheritDoc}
     */
    public static function supports_format( string $format ): bool {
        $image_types = imagetypes();
        return match ( mime_content_type( "a.{$format}" ) ) {
            'image/jpeg' => ( IMG_JPG & $image_types ) !== 0,
            'image/png' => ( IMG_PNG & $image_types ) !== 0,
            'image/gif' => ( IMG_GIF & $image_types ) !== 0,
            'image/webp' => ( IMG_WEBP & $image_types ) !== 0,
            default => false,
        };
    }

    /**
     * {@inheritDoc}
     */
    public function dump( ?string $file = null ) {
        $this->options->returnResource = true;

        parent::dump( $file );

        if ( ! $this->options->logo ) {
            $image = $this->dumpImage();

            $this->saveToFile( $image, $file );

            return $image;
        }

        $image = imagecreatefromstring( file_get_contents( $this->options->logo ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents

        $w = imagesx( $image );
        $h = imagesy( $image );

        $lw = ( ( $this->options->logoSpaceWidth - 2 ) * $this->options->scale );
		$lh = ( ( $this->options->logoSpaceHeight - 2 ) * $this->options->scale );
        $ql = ( $this->matrix->getSize() * $this->options->scale );

        imagecopyresampled( $this->image, $image, ( ( $ql - $lw ) / 2 ), ( ( $ql - $lh ) / 2 ), 0, 0, $lw, $lh, $w, $h );

        $image = $this->dumpImage();

        $this->saveToFile( $image, $file );

        return $image;
    }

    /**
     * {@inheritDoc}
     */
    protected function renderImage(): void {
        match ( $this->options->format ) {
            'bmp' => imagebmp( $this->image, null, ( $this->options->quality > 0 ) ),
            'gif' => imagegif( $this->image ),
            'jpg', 'jpeg' => imagejpeg( $this->image, null, max( -1, min( 100, $this->options->quality ) ) ),
            'webp' => imagewebp( $this->image, null, max( 0, min( 100, $this->options->quality ) ) ),
            'png' => imagepng( $this->image, null, max( 0, min( 9, (int) round( $this->options->quality / 10 ) ) ) ),
            default => imagepng( $this->image, null, max( 0, min( 9, (int) round( $this->options->quality / 10 ) ) ) ),
        };
    }
}
