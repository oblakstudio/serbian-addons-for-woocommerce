<?php
/**
 * QR_Generator_Interface class file.
 *
 * @package Serbian Addons for WooCommerce
 */

namespace Oblak\WooCommerce\Serbian_Addons\QR;

/**
 * Interface for QR Code generators.
 */
interface QR_Generator_Interface {
    /**
	 * Checks to see if current environment supports this generator.
	 *
	 * @param array $args Array of arguments to pass to the generator.
	 * @return bool
	 */
    public static function test( array $args ): bool;

    /**
     * Checks to see if the current generator supports the requested format.
     *
     * @param  string $format Format to check.
     * @return bool
     */
    public static function supports_format( string $format ): bool;
}
