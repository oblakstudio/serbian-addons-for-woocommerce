<?php //phpcs:disable Squiz.Commenting.FunctionComment.MissingParamTag, SlevomatCodingStandard
/**
 * Payment_Slip_IPS_Handler class file.
 *
 * @package Serbian Addons for WooCommerce
 * @subpackage Gateway
 */

namespace Oblak\WCSRB\Gateway;

use Oblak\WCSRB\Services\QR_Code_Manager;
use XWP\DI\Decorators\Action;
use XWP\DI\Decorators\Handler;
use PHPMailer\PHPMailer\PHPMailer;
use WC_Order;

/**
 * Adds the IPS QR data to the order, and generates the QR code
 */
#[Handler(
    tag: 'wc_payment_gateways_initialized',
    priority: 101,
    strategy: Handler::INIT_JUST_IN_TIME,
    container: 'wcsrb',
    context: Handler::CTX_CLI
)]
class Gateway_Payment_Slip_IPS_Handler {
    /**
     * Filename of the QR code image for the email.
     *
     * @var string|null
     */
    private ?string $filename = null;

    /**
     * Constructor
     *
     * @param QR_Code_Manager $qrc The QR code manager.
     */
    public function __construct( private QR_Code_Manager $qrc ) {
    }

    /**
     * Generates the QR code for the IPS payment slip.
     *
     * @param  int|WC_Order $order     The order object.
     */
    #[Action( tag: 'woocommerce_new_order', priority: 30 )]
    #[Action( tag: 'woocommerce_order_action_wcsrb_gen_ips', priority: 30 )]
    public function generate_qr_code( int|WC_Order $order ) {
        $order = \wc_get_order( $order );

        if ( ! \wcsrb_order_has_slip( $order ) ) {
            return;
        }

        $this->qrc->create( $order );
    }

    /**
     * Deletes the QR code file
     *
     * @param  int $order_id Order ID.
     */
    #[Action( tag: 'woocommerce_before_delete_order', priority: 20 )]
    #[Action( tag: 'woocommerce_before_trash_order', priority: 20 )]
    #[Action( tag: 'woocommerce_order_status_completed', priority: 20 )]
    public function delete_order_qr_code( int $order_id ) {
        $this->qrc->delete( \wc_get_order( $order_id ) );
    }

    /**
     * Show QR Code on the thank you page, and order details.
     *
     * @param  int $order_id The order ID.
     */
    #[Action( tag: 'woocommerce_thankyou_wcsrb_payment_slip', priority: 101 )]
    #[Action( tag: 'woocommerce_view_order', priority: 9 )]
    public function show_qr_code( $order_id ) {
        $order = \wc_get_order( $order_id );

        if ( ! \wcsrb_can_display_qr( $order, 'order' ) ) {
            return;
        }

        \wc_get_template(
            'checkout/payment-slip-qr-code.php',
            $this->qrc->get_template_args( $order, 'order' ),
        );
    }

    /**
     * Adds the actual payment slip to the emails
     *
     * @param  WC_Order  $order         Order object.
     * @param  bool      $sent_to_admin Whether or not the email is sent to the admin.
     * @param  bool      $plain_text    Whether or not the email is plain text.
     * @param  \WC_Email $email         Email object.
     */
    #[Action( tag: 'woocommerce_email_order_details', priority: 55 )]
    public function add_qr_code_to_email( $order, $sent_to_admin, $plain_text, $email ) {
        if (
            'customer_on_hold_order' !== $email->id ||
            $sent_to_admin || $plain_text ||
            ! \wcsrb_can_display_qr( $order, 'email' )
        ) {
            return;
        }

        $args = $this->qrc->get_template_args( $order, 'email' );

        $this->filename = $args['path'];

        echo '<div class="woocommerce-email">';
        \wc_get_template( 'checkout/payment-slip-qr-code.php', $args );
        echo '</div>';
    }

    /**
     * Adds the QR code as an inline image to the email
     *
     * @param  PHPMailer $phpmailer The PHPMailer object.
     */
    #[Action( tag: 'phpmailer_init', priority: 99 )]
    public function add_inline_image( PHPMailer $phpmailer ) {
        if ( ! $this->filename ) {
            return;
        }

        $phpmailer->addEmbeddedImage( $this->filename, 'ips-qr-code', 'ips-qr-code.jpg' );

        $this->filename = null;
    }
}
