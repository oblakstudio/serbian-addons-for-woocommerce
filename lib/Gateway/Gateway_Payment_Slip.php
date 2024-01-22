<?php //phpcs:disable Squiz.Commenting.FunctionComment.MissingParamTag
/**
 * Payment_Slip_Gateway class file.
 *
 * @package Serbian Addons for WooCommerce
 */

namespace Oblak\WooCommerce\Serbian_Addons\Gateway;

use Automattic\Jetpack\Constants;
use Oblak\WooCommerce\Gateway\Extended_Payment_Gateway;
use WC_Email;
use WC_Order;
use WP_Error;

use function Oblak\validateBankAccount;
use function Oblak\WP\Utils\invoke_class_hooks;

/**
 * Payment Slip Gateway.
 *
 * @since 2.3.0
 */
class Gateway_Payment_Slip extends Extended_Payment_Gateway {


    /**
     * Bank account.
     *
     * @var string
     */
    protected $bank_account;

    /**
     * Payment purpose.
     *
     * @var string
     */
    protected $payment_purpose;

    /**
     * Payment code.
     *
     * @var string
     */
    protected $payment_code;

    /**
     * Payment model.
     *
     * @var string
     */
    protected $payment_model;

    /**
     * Payment reference.
     *
     * @var string
     */
    protected $payment_reference;

    /**
     * Payment slip style
     *
     * @var string
     */
    protected $style;

    /**
     * QR code shown.
     *
     * @var bool
     */
    protected $qrcode_shown;

    /**
     * QR code color.
     *
     * @var string
     */
    protected $qrcode_color;

    /**
     * QR code corner color.
     *
     * @var string
     */
    protected $qrcode_corner_color;

    /**
     * QR code image.
     *
     * @var bool
     */
    protected $qrcode_image;

    /**
     * Debug mode.
     *
     * @var bool
     */
    protected $debug;

    /**
     * Company data.
     *
     * @var array
     */
    protected $company_data;

    /**
     * Class constructor.
     */
    public function __construct() {
		parent::__construct();

        $this->company_data = WCSRB()->get_settings( 'company' );

        self::$log_enabled[ self::$log_id ] = $this->debug;

        if ( is_wp_error( $this->is_valid_for_use() ) ) {
            $this->enabled = 'no';
        } else {
            new Gateway_Payment_Slip_Data_Handler( $this->get_available_settings() );
            new Gateway_Payment_Slip_IPS_Handler( $this->get_available_settings() );

            invoke_class_hooks( $this );

        }
    }

    /**
     * {@inheritDoc}
     */
    protected function get_base_props(): array {
        return array(
			'id'                 => 'wcsrb_payment_slip',
			'method_title'       => __( 'Payment Slip', 'serbian-addons-for-woocommerce' ),
			'method_description' => __( 'Have your customers pay you by sending you money via wire transfer.', 'serbian-addons-for-woocommerce' ),
			'has_fields'         => false,
        );
    }

    /**
     * {@inheritDoc}
     */
    protected function get_raw_form_fields(): array {
        return include WCRS_PLUGIN_PATH . 'config/pg-slip-settings.php';
    }

    /**
     * {@inheritDoc}
     */
    public function needs_setup() {
        return empty( $this->bank_account ) || ! validateBankAccount( $this->bank_account ) || is_wp_error( $this->is_valid_for_use() );
    }

    /**
	 * Check if this gateway is available in the user's country based on currency.
	 *
	 * @return bool|WP_Error
	 */
    public function is_valid_for_use(): bool|WP_Error {
        if ( ! in_array( get_woocommerce_currency(), array( 'RSD', 'РСД', 'din', 'din.' ), true ) ) {
            return new WP_Error( 'invalid_currency', __( 'Serbian Payment Slip does not support your store currency.', 'serbian-addons-for-woocommerce' ) );
        } elseif ( empty( WCSRB()->get_settings( 'company', 'accounts' ) ) ) {
            return new WP_Error( 'invalid_bank_account', __( 'Serbian Payment Slip requires at least one bank account.', 'serbian-addons-for-woocommerce' ) );
        } else {
            return true;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function process_payment( $order_id ) {
        $order = wc_get_order( $order_id );

        $default_order_status = 'on-hold';

        /**
         * Filters the payment slip payment order status.
         *
         * @param string   $order_status The order status.
         * @param WC_Order $order        The order object.
         * @return string                Modified order status.
         *
         * @since 2.3.0
         */
        $order_status = apply_filters( 'wcsrb_payment_slip_payment_order_status', $default_order_status, $order );

        if ( $order->get_total() > 0 ) {
            $order->update_status( $order_status, __( 'Awaiting payment', 'woocommerce' ) );
        } else {
            $order->payment_complete();
        }

        WC()->cart->empty_cart();

        return array(
            'result'   => 'success',
            'redirect' => $this->get_return_url( $order ),
        );
    }

    /**
     * Displays the payment slip on the thank you page
     *
     * @param  int $order_id Order ID.
     *
     * @hook     woocommerce_thankyou_wcsrb_payment_slip, woocommerce_view_order
     * @type     action
     * @priority 100, 7
     */
    public function show_payment_slip( $order_id ) {
        $order = wc_get_order( $order_id );

        if ( 'wcsrb_payment_slip' !== $order->get_payment_method() ) {
            return;
        }

        wc_get_template(
            'checkout/payment-slip.php',
            array_merge(
                $order->get_meta( '_payment_slip_data', true ),
                array(
                    'style'    => $this->style,
                    'order_id' => $order_id,
                )
            ),
        );
    }

    /**
     * Adds the payment slip CSS to the emails
     *
     * @param  string   $css   Email CSS.
     * @param  WC_Email $email Email object.
     * @return string          Modified email CSS.
     *
     * @hook     woocommerce_email_styles
     * @type     filter
     * @priority 9999
     */
    public function add_css_to_emails( $css, $email ) {
        if ( 'customer_on_hold_order' !== $email->id || 'wcsrb_payment_slip' !== $email->object?->get_payment_method() ) {
            return $css;
        }

        $css .= file_get_contents( WCSRB()->asset_path( 'styles/main.css' ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents

        return $css;
    }

    /**
     * Adds the actual payment slip to the emails
     *
     * @param  WC_Order $order         Order object.
     * @param  bool     $sent_to_admin Whether or not the email is sent to the admin.
     * @param  bool     $plain_text    Whether or not the email is plain text.
     * @param  WC_Email $email         Email object.
     *
     * @hook     woocommerce_email_order_details
     * @type     action
     * @priority 50
     */
    public function add_payment_slip_to_email( $order, $sent_to_admin, $plain_text, $email ) {
        if (
            'customer_on_hold_order' !== $email->id ||
            $sent_to_admin || $plain_text ||
            'wcsrb_payment_slip' !== $email->object->get_payment_method()
        ) {
            return;
        }

        Constants::set_constant( 'WCSRB_EMAIL', true );

        echo '<div class="woocommerce-email">';

        wc_get_template(
            'checkout/payment-slip.php',
            array_merge(
                $order->get_meta( '_payment_slip_data', true ),
                array(
                    'style'    => $this->style,
                    'order_id' => $order->get_id(),
                )
            ),
        );

        echo '</div>';
    }
}
