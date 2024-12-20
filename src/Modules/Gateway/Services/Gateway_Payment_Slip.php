<?php //phpcs:disable Squiz.Commenting.FunctionComment.MissingParamTag, SlevomatCodingStandard.Arrays.AlphabeticallySortedByKeys.IncorrectKeyOrder, SlevomatCodingStandard.Functions.RequireMultiLineCall, SlevomatCodingStandard.Commenting.UselessInheritDocComment
/**
 * Payment_Slip_Gateway class file.
 *
 * @package Serbian Addons for WooCommerce
 */

namespace Oblak\WCSRB\Gateway\Services;

use WC_Email;
use WC_Order;
use XWC\Interfaces\Config_Repository;
use XWP\DI\Decorators\Action;
use XWP\DI\Decorators\Filter;
use XWP\DI\Decorators\Handler;
use XWP_Asset_Bundle;

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
class Gateway_Payment_Slip extends \XWC_Payment_Gateway {
    /**
     * Gateway ID.
     *
     * @var string
     */
    public const GW_ID = 'wcsrb_payment_slip';

    /**
     * Constructor
     *
     * @param  Config_Repository $config   Config instance.
     * @param  Payments          $payments Payments utility instance.
     */
    public function __construct(
        private Config_Repository $config,
        private Payments $payments,
    ) {
        parent::__construct();
    }

    /**
     * Prefix key for settings.
     *
     * @param  string $key Field key.
     * @return string
     */
    public function get_field_key( $key ) {
        return $this->plugin_id . $this->id . '_' . $key;
    }

    /**
     * Return the name of the option in the WP DB.
     *
     * @since 2.6.0
     * @return string
     */
    public function get_option_key() {
        return $this->plugin_id . $this->id . '_settings';
    }

    /**
     * {@inheritDoc}
     */
    protected function get_base_props(): array {
        return array(
            'id'                 => self::GW_ID,
            'plugin_id'          => 'woocommerce_',
            'method_title'       => \__( 'Payment Slip', 'serbian-addons-for-woocommerce' ),
            'method_description' => \__( 'Have your customers pay you by sending you money via wire transfer.', 'serbian-addons-for-woocommerce' ),
            'user_title'         => \__( 'Payment Slip', 'serbian-addons-for-woocommerce' ),
            'user_description'   => \__( 'Pay by sending us money via wire transfer', 'serbian-addons-for-woocommerce' ),
            'has_fields'         => false,
        );
    }

    /**
     * {@inheritDoc}
     */
    protected function get_raw_form_fields(): array {
        return include WCSRB_PATH . 'config/pg-slip-settings.php';
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

        /**
         * Action hook to initialize the payment slip gateway.
         *
         * @param Gateway_Payment_Slip $gw Payment slip gateway instance.
         * @since 4.0.0
         */
        \do_action( 'wcsrb_payment_slip_init', $this );

        \xwp_load_hook_handler( $this );
    }

    /**
     * {@inheritDoc}
     */
    public function get_post_data() {
        /**
         * Var overload.
         *
         * @var array $data
         */
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
     * @return bool|\WP_Error
     */
    public function is_valid_for_use(): \WP_Error|bool {
        [ $code, $msg ] = match ( true ) {
            ! $this->payments->is_rsd()   => array(
                'invalid_currency',
                \__( 'Serbian Payment Slip does not support your store currency.', 'serbian-addons-for-woocommerce' ),
            ),
            ! $this->config->get( 'company.accounts' ) => array(
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
     * Check if the email is valid for the payment slip.
     *
     * @param  WC_Email $email     Email object.
     * @param  bool     $to_admin  Whether the email is sent to the admin.
     * @param  bool     $plaintext Whether the email is plain text.
     * @return bool
     */
    public function email_valid( WC_Email $email, bool $to_admin, bool $plaintext = false ): bool {
        return 'customer_on_hold_order' === $email->id &&
            ! $to_admin && ! $plaintext &&
            $this->slip_enabled( $email->object, true );
    }

    /**
     * Check if the order has a payment slip.
     *
     * @param  null|int|WC_Order $order       Order ID, object or null.
     * @param  bool              $unpaid_only Whether to check only for unpaid orders.
     * @return bool
     */
    public function slip_enabled( null|int|WC_Order $order, bool $unpaid_only = false ): bool {
        return \wcsrb_order_has_slip( $order, $unpaid_only );
    }

    /**
     * Can we display the payment slip?
     *
     * @param  null|int|WC_Order $order  Order ID, object or null.
     * @param  'order'|'email'   $where  Where to display the payment slip.
     * @param  bool              $unpaid Whether to display only unpaid orders.
     * @return bool
     */
    public function slip_active( null|int|WC_Order $order, string $where, bool $unpaid = true ): bool {
        return \in_array( $where, $this->display, true ) &&
            $this->slip_enabled( $order, $unpaid );
    }

    /**
     * Can we display the QR Code?
     *
     * @param  null|int|WC_Order $order  Order ID, object or null.
     * @param  'order'|'email'   $where  Where to display the payment slip.
     * @param  bool              $unpaid Whether to display only unpaid orders.
     * @return bool
     */
    public function qr_active( null|int|WC_Order $order, string $where, bool $unpaid = true ): bool {
        return \in_array( $where, $this->qrcode_shown, true ) && $this->slip_enabled( $order, $unpaid );
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
    public function show_payment_slip( int $order_id ): void {
        if ( ! $this->slip_active( $order_id, 'order' ) ) {
            return;
        }

        \wc_get_template(
            'checkout/payment-slip.php',
            \array_merge(
                $this->payments->get_data( \wc_get_order( $order_id ) ),
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
    #[Filter(
        tag: 'woocommerce_email_styles',
        priority: 9999,
        invoke:Filter::INV_PROXIED,
        args: 2,
        params: array(
            XWP_Asset_Bundle::class,
        ),
    )]
    public function add_css_to_emails( string $css, WC_Email $email, XWP_Asset_Bundle $bundle ) {
        if ( $this->email_valid( $email, false ) ) {
            $css .= $bundle['css/email/template.css']->data() . "\n";
            $css .= $bundle['css/front/main.css']->data() . "\n";
        }

        return $css;
    }

    /**
     * Adds the actual payment slip to the emails
     *
     * @param  int|WC_Order $order     Order ID or object.
     * @param  bool         $to_admin  Whether or not the email is sent to the admin.
     * @param  bool         $plaintext Whether or not the email is plain text.
     * @param  WC_Email     $email     Email object.
     */
    #[Action( tag: 'woocommerce_email_order_details', priority: 50 )]
    public function add_payment_slip_to_email( int|WC_Order $order, bool $to_admin, bool $plaintext, WC_Email $email ) {
        if ( ! $this->slip_active( $email->object, 'email' ) ) {
            return;
        }

        if ( ! $this->email_valid( $email, $to_admin, $plaintext ) ) {
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
