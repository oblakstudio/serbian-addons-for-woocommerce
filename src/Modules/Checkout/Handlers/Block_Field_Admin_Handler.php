<?php

namespace Oblak\WCSRB\Checkout\Handlers;

use XWP\DI\Decorators\Handler;

#[Handler( tag: 'woocommerce_init', priority: 98, context: Handler::CTX_ADMIN, container: 'wcsrb' )]
class Block_Field_Admin_Handler {
}
