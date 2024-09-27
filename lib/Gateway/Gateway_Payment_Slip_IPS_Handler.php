<?php //phpcs:disable Squiz.Commenting.FunctionComment.MissingParamTag, SlevomatCodingStandard
/**
 * Payment_Slip_IPS_Handler class file.
 *
 * @package Serbian Addons for WooCommerce
 * @subpackage Gateway
 */

namespace Oblak\WooCommerce\Serbian_Addons\Gateway;

use chillerlan\QRCode\Common\EccLevel;
use chillerlan\QRCode\Data\QRMatrix;
use Oblak\WooCommerce\Serbian_Addons\QR\QR_Code_Handler;
use Oblak\WP\Abstracts\Hook_Caller;
use Oblak\WP\Decorators\Action;
use PHPMailer\PHPMailer\PHPMailer;
use WC_Order;

/**
 * Adds the IPS QR data to the order, and generates the QR code
 */
class Gateway_Payment_Slip_IPS_Handler extends Hook_Caller {
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
        parent::__construct();
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

        QR_Code_Handler::instance()
            ->init( $this->get_qr_code_options( \wcsrb_slip_gw()->get_options() ) )
            ->create_file( $order );
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
        $filename = QR_Code_Handler::get_filename( \wc_get_order( $order_id ) );

        if ( ! $filename || ! \xwp_wpfs()->exists( $filename ) ) {
            return;
        }

        \xwp_wpfs()->delete( $filename );
    }

    /**
     * Get the QR code options
     *
     * @param  array<string, mixed> $options The gateway options.
     * @return array<string, mixed>
     */
    private function get_qr_code_options( array $options ): array {
        $module_values = array(
            QRMatrix::M_ALIGNMENT      => $options['qrcode_color'],
            // Aligment.
            QRMatrix::M_ALIGNMENT_DARK => $options['qrcode_color'],
            // Dark module.
            QRMatrix::M_DARKMODULE     => $options['qrcode_color'],
            QRMatrix::M_DATA           => $options['qrcode_color'],
            // Data.
            QRMatrix::M_DATA_DARK      => $options['qrcode_color'],
            QRMatrix::M_FINDER         => $options['qrcode_corner_color'],
            // Finder.
            QRMatrix::M_FINDER_DARK    => $options['qrcode_corner_color'],
            QRMatrix::M_FINDER_DOT     => $options['qrcode_corner_color'],
            QRMatrix::M_FORMAT         => $options['qrcode_color'],
            // Format.
            QRMatrix::M_FORMAT_DARK    => $options['qrcode_color'],
            QRMatrix::M_TIMING         => $options['qrcode_color'],
            // Timing.
            QRMatrix::M_TIMING_DARK    => $options['qrcode_color'],
            QRMatrix::M_VERSION        => $options['qrcode_color'],
            // Version.
            QRMatrix::M_VERSION_DARK   => $options['qrcode_color'],
        );

        $logo = $options['qrcode_image'] ? \get_option( 'site_icon', 0 ) : 0;
        $args = array(
            'eccLevel'     => EccLevel::L,
            'format'       => 'jpg',
            'moduleValues' => $module_values,
            'quality'      => 50,
            'scale'        => 15,
        );

        if ( $logo ) {
            $args = \array_merge(
                $args,
                array(
                    'addLogoSpace'    => true,
                    'eccLevel'        => EccLevel::H,
                    'logo'            => \get_attached_file( $logo ),
                    'logoSpaceHeight' => 20,
                    'logoSpaceWidth'  => 20,
				),
            );
        }

        return $args;
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
            $this->get_template_args( $order, 'display' ),
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

        $args = $this->get_template_args( $order, 'email' );

        \add_action(
            'phpmailer_init',
            fn( &$phpmailer ) => $this->add_inline_image( $phpmailer, $args['path'] )
        );

        echo '<div class="woocommerce-email">';
        \wc_get_template( 'checkout/payment-slip-qr-code.php', $args );
        echo '</div>';
    }

    /**
     * Get template arguments for the QR Code
     *
     * @param  WC_Order $order The order object.
     * @param  string   $type  The template type. Can be 'display' or 'email'.
     * @return array
     */
    private function get_template_args( WC_Order $order, string $type ): array {
        $qrc = QR_Code_Handler::instance()->init( $this->get_qr_code_options( $this->options ) );
        return array(
            'alt'  => \__( 'IPS QR Code', 'serbian-addons-for-woocommerce' ),
            'path' => $qrc::get_filename( $order ),
            'src'  => 'email' === $type
                ? 'cid:ips-qr-code'
                : $qrc->get_file_base64( $order ),
        );
    }

    /**
     * Adds the QR code as an inline image to the email
     *
     * @param  PHPMailer $phpmailer The PHPMailer object.
     * @param  string    $filepath  The QR code file path.
     */
    private function add_inline_image( PHPMailer &$phpmailer, string $filepath ) {
        $phpmailer->addEmbeddedImage( $filepath, 'ips-qr-code', 'ips-qr-code.jpg' );
    }
}
