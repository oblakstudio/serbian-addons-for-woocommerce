<?php

namespace Oblak\WCSRB\Checkout\Handlers;

use XWC\Interfaces\Config_Repository;
use XWP\DI\Decorators\Handler;

#[Handler( tag: 'woocommerce_loaded', priority: 9999, container: 'wcsrb' )]
class Field_Customization_Handler_Classic {
    /**
     * Checks if the module can be initialized.
     *
     * @param  Config_Repository $cfg Config repository instance.
     * @return bool
     */
    public static function can_initialize( Config_Repository $cfg ): bool {
        return $cfg->get( 'core.block_checkout', true );
    }

    /**
     * Constructor
     *
     * @param  Config_Repository $config Config Service.
     */
    public function __construct( private Config_Repository $config ) {
    }
}
