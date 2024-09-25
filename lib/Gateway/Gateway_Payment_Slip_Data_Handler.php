<?php //phpcs:disable SlevomatCodingStandard
/**
 * Payment_Slip_Data_Handler class file.
 *
 * @package Serbian Addons for WooCommerce
 */

namespace Oblak\WooCommerce\Serbian_Addons\Gateway;

use Oblak\WP\Abstracts\Hook_Caller;
use Oblak\WP\Decorators\Action;
use WC_Order;

/**
 * Adds payment slip metadata to the order
 */
class Gateway_Payment_Slip_Data_Handler extends Hook_Caller {
    /**
     * Constructor
     *
     * @param array<string, mixed> $options Gateway options.
     */
    public function __construct(
        /**
         * Gateway options
         *
         * @var array<string, mixed>
         */
        protected array $options,
    ) {
        $this->options['company_data'] = \WCSRB()->get_settings( 'company' );

        parent::__construct();
    }

    /**
     * Adds payment slip metadata to the order
     *
     * @param  int      $order_id Order ID.
     * @param  WC_Order $order    Order object.
     */
    #[Action( tag: 'woocommerce_new_order', priority: 10 )]
    public function add_slip_metadata( int $order_id, WC_Order $order ) {
        if ( ! $order ) {
            return;
        }

        $slip_data = array();

        foreach ( $this->get_slip_data_keys() as $key ) {
            $slip_data[ $key ] = $this->{"get_$key"}( $order );
        }

        $order->update_meta_data( '_payment_slip_data', $slip_data );
        $order->save();
    }

    /**
     * Get the payment slip data keys
     *
     * @return string[] The payment slip data keys.
     */
    protected function get_slip_data_keys(): array {
        $slip_data_keys = array(
            'customer',
            'purpose',
            'company',
            'code',
            'currency',
            'total',
            'account',
            'model',
            'reference',
        );

        // phpcs:ignore WooCommerce.Commenting
        return \apply_filters( 'woocommerce_serbian_payment_slip_data_keys', $slip_data_keys );
    }

    /**
     * Get the customer data for the order.
     *
     * @param  WC_Order $order Order object.
     * @return string          The customer.
     */
    protected function get_customer( WC_Order $order ): string {
        \add_filter(
            'woocommerce_formatted_address_replacements',
            array( $this, 'remove_extra_address_replacements' ),
            PHP_INT_MAX,
            1,
        );

        return $order->get_formatted_billing_address();
    }

    /**
     * Get the payment purpose for the order
     *
     * @param  WC_Order $order Order object.
     * @return string          The payment purpose.
     */
    protected function get_purpose( $order ) {
        $purpose = $this->options['payment_purpose'];

        /**
         * Filters the payment slip payment purpose.
         *
         * @param  string   $purpose The payment purpose.
         * @param  WC_Order $order   The order object.
         * @return string            Modified payment purpose.
         *
         * @since 2.3.0
         */
        return \apply_filters( 'woocommerce_serbian_payment_slip_purpose', $purpose, $order );
    }

    /**
     * Formats the company data for the payment slip
     *
     * @param  WC_Order $order Whether or not the data is for the QR code.
     * @return string          The formatted company data.
     */
    protected function get_company( $order ) {
        return \sprintf(
            '%s<br>%s %s<br>%s %s, %s',
            $this->options['company_data']['name'],
            $this->options['company_data']['address'],
            $this->options['company_data']['address_2'],
            $this->options['company_data']['postcode'],
            $this->options['company_data']['city'],
            \WC()->countries->countries[ $this->options['company_data']['country'] ],
        );
    }

    /**
     * Get the payment code for the order
     *
     * @param WC_Order $order Order object.
     * @return string         The payment code.
     */
    protected function get_code( $order ) {
        if ( 'auto' !== $this->options['payment_code'] ) {
            return $this->options['payment_code'];
        }

        if ( 'company' === $order->get_meta( '_billing_type', true ) ) {
            return '221';
        }

        return '289';
    }

    /**
     * Get the currency for the order
     *
     * @param  WC_Order $order Order object.
     * @return string          The currency.
     */
    protected function get_currency( $order ) {
        return $order->get_currency();
    }

    /**
     * Get the total for the order
     *
     * @param  WC_Order $order Order object.
     * @return string          The total.
     */
    protected function get_total( $order ) {
        return (float) $order->get_total( 'edit' );
    }

    /**
     * Get the account for the order
     *
     * @param  WC_Order $order Order object.
     * @return string          The account.
     */
    protected function get_account( $order ) {
        return \wcsrb_format_bank_acct( $this->options['bank_account'] );
    }

    /**
     * Get the payment model for the order
     *
     * @param  WC_Order $order  Order object.
     * @return string           The payment model.
     */
    protected function get_model( $order ) {
        $model = \wcsrb_get_payment_models()[ $this->options['payment_model'] ];

        if ( empty( $model ) ) {
            $model = '00';
        }

        /**
         * Filters the payment slip payment model.
         *
         * @param  string   $model  The payment model.
         * @param  WC_Order $order  The order object.
         * @return string           Modified payment model.
         *
         * @since 2.3.0
         */
        return \apply_filters( 'woocommerce_serbian_payment_slip_model', $model, $order );
    }

    /**
     * Get the order reference number for the given order.
     *
     * @param  WC_Order $order  Order object.
     * @return string           Alphanumeric order reference number.
     */
    protected function get_reference( $order ) {
        $replacements = \wcsrb_get_payment_reference_replacement_pairs( $order );

		return \strtr( $this->options['payment_reference'], $replacements );
	}

    /**
     * Removes the extra address replacements
     *
     * @param  array $replacements The address replacements.
     * @return array               The modified address replacements.
     */
    public function remove_extra_address_replacements( $replacements ) {
        \remove_filter(
            'woocommerce_formatted_address_replacements',
            array( $this, 'remove_extra_address_replacements' ),
            PHP_INT_MAX,
        );
        // phpcs:ignore WordPress.Arrays
        return \array_merge( $replacements, array( '{mb}'  => '', '{pib}' => '' ) );
    }
}
