<?php
/**
 * QR_Code_Options class file.
 *
 * @package Serbian Addons for WooCommerce
 */

namespace Oblak\WooCommerce\Serbian_Addons\QR;

use chillerlan\QRCode\QRCodeException;
use chillerlan\QRCode\QROptions;

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
     * Set the logo file path
     *
     * @param  string $logo Logo file path.
     *
     * @throws QRCodeException If the logo file is invalid.
     */
    protected function set_logo( ?string $logo = null ): void {
        if ( ! $logo ) {
            return;
        }

        if ( ! file_exists( $logo ) || ! is_file( $logo ) || ! is_readable( $logo ) ) {
			throw new QRCodeException( 'Invalid logo file' );
		}

		$this->logo = $logo;
    }

    /**
     * Set the desired format
     *
     * @param  string $format Desired format.
     */
    protected function set_format( string $format ) {
        if ( 'jpg' === $format ) {
            $format = 'jpeg';
        }

        $this->format = $format;
    }
}
