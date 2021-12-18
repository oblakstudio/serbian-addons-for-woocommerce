<?php

// This file intentionally left without namespace

use Oblak\WCRS\WooCommerceSerbian;

/**
 * Main Plugin Instance
 *
 * @return WooCommerceSerbian
 */
function WCSRB() {
    return WooCommerceSerbian::getInstance();
}
