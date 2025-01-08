<?php
/**
 * Field_Validate_Handler class file.
 *
 * @package Serbian Addons for WooCommerce
 * @subpackage Checkout
 */

namespace Oblak\WCSRB\Checkout\Handlers\Block;

use Automattic\WooCommerce\Blocks\Domain\Services\CheckoutFields;
use Oblak\WCSRB\Checkout\Services\Field_Validator;
use WC_Customer;
use WC_Data;
use WC_Order;
use XWC\Interfaces\Config_Repository;
use XWP\DI\Decorators\Action;
use XWP\DI\Decorators\Handler;

/**
 * Handles field validation
 *
 * ! This handler is used for Block Checkout
 *
 * @since 4.0.0
 */
#[Handler( tag: 'woocommerce_init', priority: 99, container: 'wcsrb' )]
class Field_Validate_Handler {
    /**
     * Old field names mapped to new field names.
     *
     * @var array<string,string>
     */
    private array $map = array(
        'billing_company' => 'company',
        'billing_country' => 'country',
        'billing_mb'      => 'wcsrb/mb',
        'billing_pib'     => 'wcsrb/pib',
        'billing_type'    => 'wcsrb/type',
    );

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
     * @param Field_Validator $validator Field validator instance.
     */
    public function __construct( protected Field_Validator $validator ) {
    }

    /**
     * Validates the block checkout fields.
     *
     * @param  \WP_Error           $error  Error object.
     * @param  array<string,mixed> $fields Address fields.
     * @param  string              $group  Address group.
     */
    #[Action( tag: 'woocommerce_blocks_validate_location_address_fields', priority: 1000 )]
    public function validate_block_checkout( \WP_Error $error, array $fields, string $group ): void {
        if ( 'shipping' === $group || \doing_action( 'woocommerce_after_save_address_validation' ) ) {
            return;
        }

        $address = $this->validator->remap_fields( $fields, $this->map );

        foreach ( $this->validator->validate_address( $address, $group ) as $err ) {
            $error->add( $err['code'], $err['message'], array( 'id' => $err['id'] ) );
        }
    }

    /**
     * Remove company data if the customer is not a company.
     *
     * @param  WC_Order $order Order object.
     */
    #[Action( tag: 'woocommerce_store_api_checkout_order_processed', priority: 10 )]
    public function maybe_remove_company_data( WC_Order $order ): void {
        if ( 'company' === \wcsrb_get_cf()->get_field_from_object( 'wcsrb/type', $order, 'billing' ) ) {
            return;
        }

        $this->update_meta( $order );
        $this->update_meta( new WC_Customer( $order->get_customer_id() ) );
    }

    /**
     * Update the metadata for a WC_Data object.
     *
     * @param  WC_Order|WC_Customer $obj WC_Data object.
     */
    private function update_meta( WC_Data $obj ): void {
        static $pfx;
        $pfx ??= CheckoutFields::get_group_key( 'billing' );

        \error_log( $pfx );

        if ( 0 === $obj->get_id() ) {
            return;
        }

        $obj->set_billing_company( '' );
        $obj->update_meta_data( $pfx . 'wcsrb/mb', '' );
        $obj->update_meta_data( $pfx . 'wcsrb/pib', '' );
        $obj->save();
    }
}
