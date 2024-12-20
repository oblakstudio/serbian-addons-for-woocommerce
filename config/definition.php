<?php //phpcs:disable SlevomatCodingStandard.Functions.RequireMultiLineCall.RequiredMultiLineCall

use Oblak\WCSRB\Core\Services\Config;
use Oblak\WCSRB\Core\Services\Installer;
use XWC\Interfaces\Config_Repository;

return array(
    'config.assets'          => \DI\value(
        array(
            'assets'   => array(
                'admin' => array(
                    'css/admin/admin.css',
                    'js/admin/admin.js',
                ),
                'front' => array(
                    'css/front/main.css',
                    'js/front/main.js',
                ),
            ),
            'base_dir' => WCSRB_PATH . 'dist',
            'base_uri' => \plugins_url( 'dist', WCSRB_BASE ),
            'id'       => 'wcrs',
            'manifest' => 'assets.php',
            'priority' => 500,
            'version'  => WCSRB_VER,
        ),
    ),
    Config::class            => \DI\autowire( Config::class )
        ->constructorParameter( 'args', array( 'page' => 'wcsrb' ) )
        ->constructorParameter(
            'defaults',
            array(
                'core' => array(
                    'enabled_customer_types' => 'both',
                    'field_ordering'         => true,
                    'fix_currency_symbol'    => true,
                    'remove_unneeded_fields' => false,
                ),

            ),
        ),
    Config_Repository::class => \DI\get( Config::class ),
    Installer::class         => \DI\factory( array( Installer::class, 'instance' ) ),
    XWC_Config::class        => \DI\get( Config::class ),
    XWP_Asset_Bundle::class  => \DI\factory( array( XWP_Asset_Loader::class, 'get_bundle' ) )->parameter( 'id', 'wcrs' ),
);
