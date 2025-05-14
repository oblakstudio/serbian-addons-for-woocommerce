<?php
/**
 * DI Container definitions
 *
 * @package Serbian Addons for WooCommerce
 * @subpackage Config
 */

use chillerlan\QRCode\QRCode;
use Oblak\WCSRB\QR\QR_Code_Options;
use Oblak\WCSRB\QR\QR_Generator_GD;
use Oblak\WCSRB\QR\QR_Generator_ImageMagick;
use Oblak\WCSRB\Utils\Installer;

return array(
    'ips.basedir'           => \DI\factory(
        static fn() => \defined( 'WCRS_IPS_DIR' )
            ? WCRS_IPS_DIR
            : \wp_upload_dir()['basedir'] . '/wcrs-ips',
    ),
    'ips.generator'         => \DI\factory(
        static fn() => \class_exists( \Imagick::class )
            ? QR_Generator_ImageMagick::class
            : QR_Generator_GD::class,
    ),
    Installer::class        => \DI\factory( array( Installer::class, 'instance' ) ),
    QRCode::class           => \DI\factory( static fn( QR_Code_Options $opts ) => new QRCode( $opts ) ),
    XWP_Asset_Bundle::class => \DI\factory( static fn() => XWP_Asset_Loader::get_bundle( 'wcrs' ) ),
);
