<?php //phpcs:disable Squiz.Commenting.FunctionComment.MissingParamTag, SlevomatCodingStandard.Arrays.AlphabeticallySortedByKeys.IncorrectKeyOrder, SlevomatCodingStandard.Functions.RequireMultiLineCall, SlevomatCodingStandard.Commenting.UselessInheritDocComment
/**
 * Payment_Slip_Gateway class file.
 *
 * @package Serbian Addons for WooCommerce
 */

namespace Oblak\WCSRB\Gateway;

use Oblak\WCSRB\Services\Config;
use Oblak\WCSRB\Services\Payments;
use WC_Email;
use WC_Order;
use WP_Error;
use XWC\Gateway\Gateway_Base;
use XWP\DI\Decorators\Action;
use XWP\DI\Decorators\Filter;
use XWP\DI\Decorators\Handler;

/**
 * Payment Slip Gateway.
 *
 * @since 2.3.0
 *
 * @property-read bool   $debug Debug mode.
 *
 * @property-read array  $display           Display location.
 * @property-read string $bank_account      Bank account.
 * @property-read string $payment_purpose   Payment purpose.
 * @property-read string $payment_code      Payment code.
 * @property-read string $payment_model     Payment model.
 * @property-read string $payment_reference Payment reference.
 * @property-read string $style             Payment slip style.
 *
 * @property-read array  $qrcode_shown        QR code shown.
 * @property-read string $qrcode_color        QR code color.
 * @property-read string $qrcode_corner_color QR code corner color.
 * @property-read bool   $qrcode_image        QR code image.
 *
 * @property-read array $company Company data.
 */
#[Handler(
    tag: 'wc_payment_gateways_initialized',
    priority: 101,
    strategy: Handler::INIT_DYNAMICALY,
    container: 'wcsrb',
)]
class Gateway_Payment_Slip extends Gateway_Base {
    /**
     * Constructor
     *
     * @param  Config   $config   Config instance.
     * @param  Payments $payments Payments utility instance.
     */
    public function __construct(
        private Config $config,
        private Payments $payments,
    ) {
        parent::__construct();
    }

    /**
     * {@inheritDoc}
     */
    protected function get_base_props(): array {
        return array(
			'id'                 => 'wcsrb_payment_slip',
			'method_title'       => \__( 'Payment Slip', 'serbian-addons-for-woocommerce' ),
			'method_description' => \__( 'Have your customers pay you by sending you money via wire transfer.', 'serbian-addons-for-woocommerce' ),
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
     * Loads settings from the database.
     */
    public function init_settings() {
        parent::init_settings();

        if ( ! \is_array( $this->settings['qrcode_shown'] ) ) {
            $this->settings['qrcode_shown'] = 'yes' === $this->settings['qrcode_shown']
            ? 'order,email'
            : '';
        }
        $this->settings['qrcode_shown'] = \wc_string_to_array( $this->settings['qrcode_shown'] );
        $this->settings['qrcode_image'] = \wc_bool_to_string( 0 < \intval( \get_option( 'site_icon', 0 ) ) && \wc_string_to_bool( $this->settings['qrcode_image'] ) );
        $this->settings['display']      = \wc_string_to_array( $this->settings['display'] );
        $this->settings['company']      = $this->config->get( 'company' );
    }

    /**
     * {@inheritDoc}
     */
    public function init_gateway(): void {
        if ( \is_wp_error( $this->is_valid_for_use() ) || ! \wc_string_to_bool( $this->enabled ) ) {
            return;
        }

        \xwp_register_hook_handler( Gateway_Payment_Slip_IPS_Handler::class );
        \xwp_load_hook_handler( $this );
    }

    /**
     * {@inheritDoc}
     */
    public function get_post_data() {
        $data = \xwp_post_arr();

        $data[ $this->get_option_key() . '_display' ]      ??= array();
        $data[ $this->get_option_key() . '_qrcode_shown' ] ??= array();

        return $data;
    }

    /**
     * {@inheritDoc}
     */
    public function needs_setup() {
        return ! $this->bank_account || \is_wp_error( $this->is_valid_for_use() );
    }

    /**
	 * Check if this gateway is available in the user's country based on currency.
	 *
	 * @return bool|WP_Error
	 */
    public function is_valid_for_use(): bool|\WP_Error {
        [ $code, $msg ] = match ( true ) {
            ! \wcsrb_is_rsd( \get_woocommerce_currency() )   => array(
                'invalid_currency',
                \__( 'Serbian Payment Slip does not support your store currency.', 'serbian-addons-for-woocommerce' ),
            ),
            ! $this->config->get( 'company', 'accounts' ) => array(
                'invalid_bank_account',
                \__( 'Serbian Payment Slip requires at least one bank account.', 'serbian-addons-for-woocommerce' ),
            ),
            default                                           => array( '', '' ),
        };

        return $code && $msg ? new \WP_Error( $code, $msg ) : true;
    }

    /**
     * {@inheritDoc}
     */
    public function process_payment( $order_id ) {
        $order = \wc_get_order( $order_id );

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
        $order_status = \apply_filters( 'wcsrb_payment_slip_payment_order_status', $default_order_status, $order );

        if ( $order->get_total() > 0 ) {
            $order->update_status( $order_status, \__( 'Awaiting payment', 'woocommerce' ) );
        } else {
            $order->payment_complete();
        }

        \WC()->cart->empty_cart();

        return array(
            'result'   => 'success',
            'redirect' => $this->get_return_url( $order ),
        );
    }

    /**
     * Adds payment slip metadata to the order
     *
     * @param  int|WC_Order $order Order ID or object.
     */
    #[Action( tag: 'woocommerce_new_order', priority: 10 )]
    #[Action( tag: 'woocommerce_order_action_wcsrb_gen_ips', priority: 10 )]
    public function add_payment_data( int|WC_Order $order ) {
        $order = \wc_get_order( $order );

        $data = array(
            'model'     => $this->payment_model,
            'reference' => $this->payment_reference,
            'purpose'   => $this->payment_purpose,
            'code'      => $this->payment_code,
            'account'   => $this->bank_account,
        );

        $order->delete_meta_data( '_payment_slip_data' );
        $order->delete_meta_data( '_payment_slip_ips_data' );
        $order->update_meta_data( '_wcsrb_payment_data', $data );
        $order->save();
    }

    /**
     * Displays the payment slip on the thank you page
     *
     * @param  int $order_id Order ID.
     */
    #[Action( tag: 'woocommerce_thankyou_wcsrb_payment_slip', priority: 100 )]
    #[Action( tag: 'woocommerce_view_order', priority: 7 )]
    public function show_payment_slip( $order_id ) {
        $order = \wc_get_order( $order_id );

        if ( ! \wcsrb_can_display_slip( $order, 'order' ) ) {
            return;
        }

        \wc_get_template(
            'checkout/payment-slip.php',
            \array_merge(
                $this->payments->get_data( $order ),
                array(
                    'style'    => $this->style,
                    'order_id' => $order_id,
                ),
            ),
        );
    }

    /**
     * Adds the payment slip CSS to the emails
     *
     * @param  string   $css   Email CSS.
     * @param  WC_Email $email Email object.
     * @return string           Modified email CSS.
     */
    #[Filter( tag: 'woocommerce_email_styles', priority: 9999 )]
    public function add_css_to_emails( string $css, WC_Email $email ) {
        if ( 'customer_on_hold_order' === $email->id && \wcsrb_order_has_slip( $email->object, true ) ) {
            $css .= \WCSRB()->asset_data( 'css/email/template.css' ) . "\n";
            $css .= \WCSRB()->asset_data( 'css/front/main.css' ) . "\n";
        }

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
    #[Action( tag: 'woocommerce_email_order_details', priority: 50 )]
    public function add_payment_slip_to_email( $order, $sent_to_admin, $plain_text, WC_Email $email ) {
        if (
            $plain_text ||
            $sent_to_admin ||
            'customer_on_hold_order' !== $email->id ||
            ! \wcsrb_can_display_slip( $email->object, 'email' )
        ) {
            return;
        }

        echo '<div class="woocommerce-email">';

        \wc_get_template(
            'checkout/payment-slip.php',
            \array_merge(
                $this->payments->get_data( $order ),
                array(
                    'style'    => $this->style,
                    'order_id' => $order->get_id(),
                ),
            ),
        );

        echo '</div>';
    }
}
