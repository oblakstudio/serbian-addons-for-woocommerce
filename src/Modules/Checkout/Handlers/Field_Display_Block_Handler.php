<?php

namespace Oblak\WCSRB\Checkout\Handlers;

use XWP\DI\Decorators\Filter;
use XWP\DI\Decorators\Handler;

#[Handler( tag: 'woocommerce_init', priority: 99, container: 'wcsrb' )]
class Field_Display_Block_Handler {
    private function did_blocks(): bool {
        return \did_action( 'woocommerce_blocks_enqueue_checkout_block_scripts_before' ) > 0;
    }
    /**
     * Modifies the address format for Serbia to include necessary company information
     *
     * @param  array<string,string> $formats Address formats.
     * @return array<string,string>
     */
    #[Filter( tag: 'woocommerce_localisation_address_formats', priority: 10000, context: Filter::CTX_FRONTEND )]
    public function modify_address_format( $formats ) {
        if ( $this->did_blocks() && \is_checkout() && ! \is_order_received_page() ) {
            $formats['RS'] = \str_replace( array( '{mb}', '{pib}' ), '', $formats['RS'] );
        }

        return $formats;
    }
}
