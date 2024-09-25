<?php
/**
 * Address_Field_Controller class file.
 *
 * @package Serbian Addons for WooCommerce
 */

namespace Oblak\WCSRB\Core;

use Oblak\WP\Abstracts\Hook_Caller;
use Oblak\WP\Decorators\Action;
use Oblak\WP\Decorators\Hookable;

/**
 * Changes the fields on the checkout page
 *
 * @since 3.8.0
 */
#[Hookable( 'woocommerce_init', 99 )]
class Address_Validate_Controller extends Hook_Caller {
    /**
     * Validates the saved address.
     *
     * @param  int    $user_id User ID.
     * @param  string $type    Address type being edited - billing or shipping.
     */
    #[Action( tag: 'woocommerce_after_save_address_validation', priority: 99 )]
    public function validate_save_address( int $user_id, string $type ) {
        foreach ( \WCSRB()->validator()->validate_fields( \xwp_post_arr(), $type ) as $error ) {
            \wc_add_notice( $error['message'], 'error', array( 'id' => $error['id'] ) );
        }
    }


    /**
     * Validates the checkout fields.
     *
     * @param  array<string,array> $fields Address fields.
     * @param  \WP_Error           $error  Error object.
     */
    #[Action( 'woocommerce_after_checkout_validation', 0 )]
    public function validate_checkout( array $fields, \WP_Error $error ) {
        foreach ( \WCSRB()->validator()->validate_fields( $fields, 'billing' ) as $err ) {
            $error->add( $err['code'], $err['message'], array( 'id' => $err['id'] ) );
        }
    }
}
