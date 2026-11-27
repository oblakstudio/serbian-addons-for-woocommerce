<?php
/**
 * Block_Checkout_Data_Controller class file.
 *
 * @package Serbian Addons for WooCommerce
 */

namespace Oblak\WCSRB\Core;

use Automattic\WooCommerce\Blocks\Domain\Services\CheckoutFields;
use Automattic\WooCommerce\Blocks\Package;
use Oblak\WCSRB\Services\Config;
use WC_Customer;
use WC_Order;
use XWP\DI\Decorators\Action;
use XWP\DI\Decorators\Filter;
use XWP\DI\Decorators\Handler;

/**
 * Keeps the block checkout's customer fields where the classic checkout keeps them.
 *
 * Customer type, MB and PIB go to `_billing_type`, `_billing_mb` and `_billing_pib` (`billing_*` on the customer),
 * the company name to the billing company - and to the shipping company of company orders. Payment slips, IPS QR
 * codes, the address format, emails, admin and eFiskal read these, so block checkout orders work with them as is.
 *
 * @since 4.0.0
 */
#[Handler( tag: 'woocommerce_init', priority: 99, container: 'wcsrb' )]
class Block_Checkout_Data_Controller {
    /**
     * Constructor
     *
     * @param  Config $config Config Service.
     */
    public function __construct( private Config $config ) {
    }

    /**
     * Stores the customer type, company name, MB and PIB on the order.
     *
     * Runs after the Store API has set the addresses and the contact fields, before the order is validated and the
     * payment slip data (payment code by customer type) is generated.
     *
     * @param  WC_Order $order Order being placed.
     */
    #[Action( tag: 'woocommerce_store_api_checkout_update_order_from_request', priority: 10 )]
    public function store_order_data( WC_Order $order ): void {
        $serbian    = 'RS' === $order->get_billing_country();
        $company    = (string) $this->field( 'company', $order );
        $is_company = $serbian && (
            'company' === $this->config->get( 'core', 'enabled_customer_types' ) || true === $this->field( 'is-company', $order )
        );

        $order->update_meta_data( '_billing_type', $is_company ? 'company' : 'person' );
        $order->set_billing_company( $serbian && ! $is_company ? '' : $company );
        $order->set_shipping_company( $is_company ? $company : '' );

        foreach ( array( 'mb', 'pib' ) as $key ) {
            $is_company
                ? $order->update_meta_data( "_billing_{$key}", (string) $this->field( $key, $order ) )
                : $order->delete_meta_data( "_billing_{$key}" );
        }
    }

    /**
     * Removes WooCommerce's copies of the contact fields from the placed order.
     *
     * The classic keys are the only copy: WooCommerce's own (even an unticked toggle, '0') would make the block order
     * confirmation print an empty "Additional information" section. Done this late because WooCommerce < 11 writes
     * them to the order again after store_order_data().
     *
     * @param  WC_Order $order Placed order.
     */
    #[Action( tag: 'woocommerce_store_api_checkout_order_processed', priority: 5 )]
    public function remove_order_copies( WC_Order $order ): void {
        foreach ( Block_Checkout_Field_Controller::FIELDS as $key ) {
            $order->delete_meta_data( "_wc_other/wcsrb/{$key}" );
        }

        $order->save();
    }

    /**
     * Copies the customer type, MB and PIB to the customer, after WooCommerce synced the order addresses to it.
     *
     * Also removes WooCommerce's copies of the contact fields: the classic keys are the only source, so an address
     * edited in My Account is what the next block checkout shows (prefill()).
     *
     * @param  WC_Order $order Placed order.
     */
    #[Action( tag: 'woocommerce_store_api_checkout_order_processed', priority: 10 )]
    public function store_customer_data( WC_Order $order ): void {
        if ( ! $order->get_customer_id() ) {
            return;
        }

        $customer = new WC_Customer( $order->get_customer_id() );
        $customer->update_meta_data( 'billing_type', $order->get_meta( '_billing_type' ) );

        foreach ( array( 'mb', 'pib' ) as $key ) {
            'company' === $order->get_meta( '_billing_type' )
                ? $customer->update_meta_data( "billing_{$key}", $order->get_meta( "_billing_{$key}" ) )
                : $customer->delete_meta_data( "billing_{$key}" );
        }

        foreach ( Block_Checkout_Field_Controller::FIELDS as $key ) {
            $customer->delete_meta_data( "_wc_other/wcsrb/{$key}" );
        }

        $customer->save();
    }

    /**
     * Prefills the contact fields from the customer's classic data.
     *
     * Customers only: WooCommerce before 11.3 asks for these defaults when it shows an order in admin too, which
     * would list the fields a second time.
     *
     * @param  mixed $value     Default value.
     * @param  mixed $group     Field group.
     * @param  mixed $wc_object Customer or order.
     * @return mixed
     */
    #[Filter( tag: 'woocommerce_get_default_value_for_wcsrb/is-company', priority: 10, args: 3 )]
    #[Filter( tag: 'woocommerce_get_default_value_for_wcsrb/company', priority: 10, args: 3 )]
    #[Filter( tag: 'woocommerce_get_default_value_for_wcsrb/mb', priority: 10, args: 3 )]
    #[Filter( tag: 'woocommerce_get_default_value_for_wcsrb/pib', priority: 10, args: 3 )]
    public function prefill( mixed $value, mixed $group, mixed $wc_object ): mixed {
        if ( ! $wc_object instanceof WC_Customer ) {
            return $value;
        }

        $customer = $this->saved_customer( $wc_object );

        return match ( \substr( \current_filter(), \strlen( 'woocommerce_get_default_value_for_wcsrb/' ) ) ) {
            // Storage format - WooCommerce converts '1' / '0' to the checkbox value.
            'is-company' => 'company' === \wcsrb_get_customer_type( $customer ) ? '1' : '0',
            'company'    => $customer->get_billing_company(),
            'mb'         => (string) $customer->get_meta( 'billing_mb' ),
            'pib'        => (string) $customer->get_meta( 'billing_pib' ),
            default      => $value,
        };
    }

    /**
     * The saved customer behind a (session) customer.
     *
     * The Checkout block resets the core Company it hides on the session customer, so the company name is read
     * from the customer's account. Guests keep their session data.
     *
     * @param  WC_Customer $customer Customer, possibly the session one.
     * @return WC_Customer
     */
    private function saved_customer( WC_Customer $customer ): WC_Customer {
        return $customer->get_id() ? new WC_Customer( $customer->get_id() ) : $customer;
    }

    /**
     * Contact field value stored on the order (sanitized and validated by WooCommerce).
     *
     * @param  string   $key   Field key, without the namespace.
     * @param  WC_Order $order Order.
     * @return mixed
     */
    private function field( string $key, WC_Order $order ): mixed {
        return Package::container()->get( CheckoutFields::class )->get_field_from_object( "wcsrb/{$key}", $order, 'other' );
    }
}
