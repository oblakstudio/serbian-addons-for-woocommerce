<?php //phpcs:disable Squiz.Commenting.FunctionComment.MissingParamTag
/**
 * Payment_Slip_Gateway class file.
 *
 * @package Serbian Addons for WooCommerce
 */

namespace Oblak\WCRS\WooCommerce\Gateway;

use WC_Email;
use WC_Logger;
use WC_Order;
use WC_Payment_Gateway;

use function Oblak\validateBankAccount;
use function Oblak\WCRS\Utils\get_payment_models;
use function Oblak\WCRS\Utils\get_payment_reference_replacement_pairs;

/**
 * Payment Slip Gateway.
 *
 * @since 2.3.0
 */
class Payment_Slip_Gateway extends WC_Payment_Gateway {

    /**
	 * Whether or not logging is enabled
	 *
	 * @var bool
	 */
	public static $log_enabled = false;

	/**
	 * Logger instance
	 *
	 * @var WC_Logger
	 */
	public static $log = false;

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
        /**
         * Filters the payment slip icon.
         *
         * @param  string $icon The icon HTML.
         * @return string       The icon HTML.
         *
         * @since 2.3.0
         */
        $this->icon               = apply_filters( 'wcsrb_payment_slip_icon', '' );
        $this->id                 = 'wcsrb_payment_slip';
        $this->title              = __( 'Payment Slip', 'serbian-addons-for-woocommerce' );
        $this->method_title       = __( 'Payment Slip', 'serbian-addons-for-woocommerce' );
        $this->method_description = __( 'Have your customers pay you by sending you money via wire transfer.', 'serbian-addons-for-woocommerce' );
        $this->has_fields         = true;

		$this->init_form_fields();
        $this->init_settings();

        $this->title               = $this->get_option( 'title' );
		$this->description         = $this->get_option( 'description' );
        $this->style               = $this->get_option( 'style', 'modern' );
        $this->bank_account        = $this->get_option( 'bank_account' );
        $this->payment_purpose     = $this->get_option( 'payment_purpose' );
        $this->payment_code        = $this->get_option( 'payment_code' );
        $this->payment_model       = $this->get_option( 'payment_model' );
        $this->payment_reference   = $this->get_option( 'payment_reference' );
        $this->qrcode_shown        = 'yes' === $this->get_option( 'qrcode_shown', 'yes' );
        $this->qrcode_color        = $this->get_option( 'qrcode_color', '#000000' );
        $this->qrcode_corner_color = $this->get_option( 'qrcode_corner_color', '#000000' );
        $this->qrcode_image        = 'yes' === $this->get_option( 'qrcode_image', 'no' );
        $this->debug               = 'yes' === $this->get_option( 'debug', 'no' );
        $this->company_data        = WCSRB()->get_settings( 'company' );

        self::$log_enabled = $this->debug;

        add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
        add_action( 'woocommerce_thankyou_' . $this->id, array( $this, 'show_payment_slip' ), 100 );
        add_action( 'woocommerce_view_order', array( $this, 'show_payment_slip' ), 9 );

        // Email actions.
        add_filter( 'woocommerce_email_styles', array( $this, 'add_css_to_emails' ), 9999, 2 );
        add_filter( 'woocommerce_email_order_details', array( $this, 'add_payment_slip_to_email' ), 50, 4 );

        if ( ! $this->is_valid_for_use() ) {
            $this->enabled = 'no';
        }
    }

    /**
     * {@inheritDoc}
     */
    public function needs_setup() {
        return empty( $this->bank_account ) || ! validateBankAccount( $this->bank_account ) || ! $this->is_valid_for_use();
    }

    /**
	 * Logging method.
	 *
	 * @param string $message Log message.
	 * @param string $level Optional. Default 'info'. Possible values:
	 *                      emergency|alert|critical|error|warning|notice|info|debug.
	 */
	public static function log( $message, $level = 'info' ) {
        if ( ! self::$log_enabled ) {
            return;
        }

        if ( empty( self::$log ) ) {
            self::$log = wc_get_logger();
        }

		self::$log->log( $level, $message, array( 'source' => 'payment-slip' ) );
	}

    /**
	 * Processes and saves options.
	 * If there is an error thrown, will continue to save and validate fields, but will leave the erroring field out.
	 *
	 * @return bool was anything saved?
	 */
	public function process_admin_options() {
		$saved = parent::process_admin_options();

		// Maybe clear logs.
		if ( 'yes' !== $this->get_option( 'debug', 'no' ) ) {
			if ( empty( self::$log ) ) {
				self::$log = wc_get_logger();
			}
			self::$log->clear( 'payment-slip' );
		}

		return $saved;
	}

    /**
	 * Check if this gateway is available in the user's country based on currency.
	 *
	 * @return bool
	 */
    public function is_valid_for_use() {
        return in_array( get_woocommerce_currency(), array( 'RSD', 'РСД', 'din', 'din.' ), true );
    }

    /**
     * {@inheritDoc}
     */
    public function admin_options() {
        if ( $this->is_valid_for_use() ) {
            parent::admin_options();
            return;
        }

        ?>
        <div class="inline error">
            <p>
                <strong>
                    <?php esc_html_e( 'Gateway disabled', 'woocommerce' ); ?>
                </strong>:
                <?php esc_html_e( 'Serbian Payment Slip does not support your store currency.', 'serbian-addons-for-woocommerce' ); ?>
            </p>
        </div>
        <?php
    }

    /**
     * {@inheritDoc}
     */
    public function init_form_fields() {
        $this->form_fields = include WCRS_PLUGIN_PATH . 'config/pg-slip-settings.php';
    }

    /**
     * {@inheritDoc}
     */
    public function process_payment( $order_id ) {
        $order = wc_get_order( $order_id );

        $order->add_meta_data( '_payment_slip_data', $this->get_slip_data( $order ), true );
        $order->save();

        /**
         * Filters the payment slip payment order status.
         *
         * @param string   $order_status The order status.
         * @param WC_Order $order        The order object.
         * @return string                Modified order status.
         *
         * @since 2.3.0
         */
        $order_status = apply_filters( 'wcsrb_payment_slip_payment_order_status', 'on-hold', $order );

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
     */
    public function show_payment_slip( $order_id ) {
        $order = wc_get_order( $order_id );

        if ( 'wcsrb_payment_slip' !== $order->get_payment_method() ) {
            return;
        }

        wc_get_template(
            'checkout/payment-slip.php',
            $this->get_complete_slip_data( $order )
        );
    }

    /**
     * Adds the payment slip CSS to the emails
     *
     * @param  string   $css   Email CSS.
     * @param  WC_Email $email Email object.
     * @return string          Modified email CSS.
     */
    public function add_css_to_emails( $css, $email ) {
        if ( 'customer_on_hold_order' !== $email->id && 'wcsrb_payment_slip' !== $email->object->get_payment_method() ) {
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
     */
    public function add_payment_slip_to_email( $order, $sent_to_admin, $plain_text, $email ) {
        if ( 'customer_on_hold_order' !== $email->id || $sent_to_admin || $plain_text ) {
            return;
        }

        $payment_slip_table = wc_get_template_html(
            'checkout/payment-slip.php',
            $this->get_complete_slip_data( $order )
        );

        printf(
            '<div class="woocommerce-email">%s</div>',
            wp_kses_post( $payment_slip_table ),
        );
    }

    /**
     * Get the complete payment slip data for the given order.
     *
     * @param  WC_Order $order Order object.
     * @return array           The payment slip data.
     */
    protected function get_complete_slip_data( $order ) {
        return array_merge(
            $order->get_meta( '_payment_slip_data', true ),
            array(
                'style'     => $this->style,
                'show_qr'   => $this->qrcode_shown,
                'dot_color' => $this->qrcode_color,
                'cor_color' => $this->qrcode_corner_color,
                'qr_image'  => $this->qrcode_image,
            )
        );
    }

    /**
     * Get the payment slip data for the given order.
     *
     * @param  WC_Order $order Order ID.
     * @return array           The payment slip data.
     */
    protected function get_slip_data( $order ) {
        $slip_data = array(
            'customer'  => $this->get_customer_data( $order ),
            'purpose'   => $this->get_payment_purpose( $order ),
            'company'   => $this->get_company_data(),
            'code'      => $this->get_payment_code( $order ),
            'currency'  => $this->get_currency( $order ),
            'total'     => $this->get_total( $order ),
			'account'   => $this->get_bank_account(),
            'model'     => $this->get_payment_model( $order ),
            'reference' => $this->get_payment_reference( $order ),
            'qr_code'   => array(
                'K'  => 'PR',
				'V'  => '01',
				'C'  => '1',
				'R'  => $this->get_bank_account( true ),
				'N'  => $this->get_company_data( true ),
				'I'  => $this->get_currency( $order ) . $this->get_total( $order ),
				'P'  => $this->get_customer_data( $order, true ),
				'SF' => $this->get_payment_code( $order ),
				'S'  => $this->get_payment_purpose( $order ),
				'RO' => $this->get_payment_model( $order, true ) . $this->get_payment_reference( $order, true ),
            ),
        );

        /**
         * Filters the payment slip data.
         *
         * @param  array    $slip_data The payment slip data.
         * @param  WC_Order $order     The order object.
         * @return array               Modified payment slip data.
         *
         * @since 2.3.0
         */
        return apply_filters( 'wcsrb_payment_slip_data', $slip_data, $order );
    }

    /**
     * Get the customer data for the payment slip
     *
     * @param  WC_Order $order   Order object.
     * @param  bool     $for_qr  Whether or not the data is for the QR code.
     * @return string            The formatted customer data.
     */
    protected function get_customer_data( $order, $for_qr = false ) {
        return $for_qr
            ? preg_replace( '/<br\/?>/', '', $order->get_formatted_billing_address() )
            : $order->get_formatted_billing_address();
    }

    /**
     * Get the payment purpose for the order
     *
     * @param  WC_Order $order Order object.
     * @return string          The payment purpose.
     */
    protected function get_payment_purpose( $order ) {
        $purpose = $this->payment_purpose;

        /**
         * Filters the payment slip payment purpose.
         *
         * @param  string   $purpose The payment purpose.
         * @param  WC_Order $order   The order object.
         * @return string            Modified payment purpose.
         *
         * @since 2.3.0
         */
        return apply_filters( 'wcsrb_payment_slip_purpose', $purpose, $order );
    }

    /**
     * Formats the company data for the payment slip
     *
     * @param  bool $for_qr Whether or not the data is for the QR code.
     * @return string       The formatted company data.
     */
    protected function get_company_data( $for_qr = false ) {
        $separator = $for_qr ? "\n" : '<br>';
        return sprintf(
            '%s%s%s %s%s%s %s, %s',
            $this->company_data['name'],
            $separator,
            $this->company_data['address'],
            $this->company_data['address_2'],
            $separator,
            $this->company_data['postcode'],
            $this->company_data['city'],
            $this->company_data['country']
        );
    }

    /**
     * Get the payment code for the order
     *
     * @param WC_Order $order Order object.
     * @return string         The payment code.
     */
    protected function get_payment_code( $order ) {
        if ( 'auto' !== $this->payment_code ) {
            return $this->payment_code;
        }

        if ( $order->get_meta( '_billing_type', true ) === 'company' ) {
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
     * @param WC_Order $order Order object.
     * @return string         The total.
     */
    protected function get_total( $order ) {
        return number_format( $order->get_total(), 2, wc_get_price_decimal_separator(), wc_get_price_thousand_separator() );
    }

    /**
     * Get the bank account for the order
     *
     * QR Code needs zeroes and no dashes, humans do not
     *
     * @param  bool $for_qr Whether or not the data is for the QR code.
     * @return string       The bank account.
     */
    protected function get_bank_account( $for_qr = false ) {
        $separator = '-';
        $parts     = str_contains( $this->bank_account, '-' )
            ? explode( '-', $this->bank_account )
            : array(
                substr( $this->bank_account, 0, 3 ),
                ltrim( substr( $this->bank_account, 3, -2 ), '0' ),
                substr( $this->bank_account, -2 ),
            );

        if ( $for_qr ) {
            $separator = '';
            $parts[1]  = str_pad( $parts[1], 13, '0', STR_PAD_LEFT );
        }

        return sprintf(
            '%s%s%s%s%s',
            $parts[0],
            $separator,
            $parts[1],
            $separator,
            $parts[2]
        );
    }

    /**
     * Get the payment model for the order
     *
     * @param  WC_Order $order  Order object.
     * @param  bool     $for_qr Whether or not the data is for the QR code.
     * @return string           The payment model.
     */
    protected function get_payment_model( $order, $for_qr = false ) {
        $model = get_payment_models()[ $this->payment_model ];

        if ( $for_qr && empty( $model ) ) {
            $model = '00';
        }

        /**
         * Filters the payment slip payment model.
         *
         * @param  string   $model  The payment model.
         * @param  WC_Order $order  The order object.
         * @param  bool     $for_qr Whether or not the data is for the QR code.
         * @return string           Modified payment model.
         *
         * @since 2.3.0
         */
        return apply_filters( 'wcsrb_payment_slip_model', $model, $order, $for_qr );
    }

    /**
     * Get the order reference number for the given order.
     *
     * @param  WC_Order $order  Order object.
     * @param  bool     $for_qr Whether or not the data is for the QR code.
     * @return string           Alphanumeric order reference number.
     */
    protected function get_payment_reference( $order, $for_qr = false ) {
        $replacements = get_payment_reference_replacement_pairs( $order );

        if ( $for_qr ) {
            $replacements['-'] = '';
        }

		return strtr(
            $this->payment_reference,
            $replacements,
		);
	}
}
