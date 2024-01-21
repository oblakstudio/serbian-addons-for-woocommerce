<?php
/**
 * Payment_Slip_IPS_Handler class file.
 *
 * @package Serbian Addons for WooCommerce
 * @subpackage Gateway
 */

namespace Oblak\WooCommerce\Serbian_Addons\Gateway;

use Automattic\Jetpack\Constants;
use chillerlan\QRCode\Common\EccLevel;
use chillerlan\QRCode\Data\QRMatrix;
use Oblak\WooCommerce\Serbian_Addons\QR\QR_Code_Handler;
use Oblak\WP\Abstracts\Hook_Runner;
use PHPMailer\PHPMailer\PHPMailer;
use WC_Order;

use function Oblak\WooCommerce\Serbian_Addons\Utils\get_ips_basedir;

/**
 * Adds the IPS QR data to the order, and generates the QR code
 */
class Gateway_Payment_Slip_IPS_Handler extends Hook_Runner {
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
        protected array $options
    ) {
        parent::__construct();
    }

    //phpcs:disable
    #region IPS Data
    //phpcs:enable

    /**
     * Adds payment slip metadata to the order
     *
     * @param  WC_Order|null $order Order object.
     *
     * @hook     woocommerce_checkout_order_created
     * @type     action
     * @priority 20
     */
    public function add_ips_metadata( WC_Order $order ) {
        $slip_data = $order->get_meta( '_payment_slip_data', true );

        if ( empty( $slip_data ) ) {
            return;
        }

        $qr_data = array();

        foreach ( $this->get_ips_data_keys() as $key => $keys ) {
            $value = '';

            foreach ( $keys as $prop ) {
                $value .= match ( true ) {
                    method_exists( $this, "format_{$prop}" ) => $this->{"format_{$prop}"}( $slip_data[ $prop ] ),
                    (bool) preg_match( '<br/?>', $slip_data[ $prop ] ?? '' ) => preg_replace( '/<br\/?>/', "\n", $slip_data[ $prop ] ),
                    array_key_exists( $prop, $slip_data )    => $slip_data[ $prop ],
                    default                                  =>  $prop,
                };
			}

            $qr_data[] = sprintf( '%s:%s', $key, $value );
        }

        $order->update_meta_data( '_payment_slip_ips_data', implode( '|', $qr_data ) );
        $order->save();
    }

    /**
     * Get the IPS QR data keys
     *
     * @return array<string, array<int, string>> The IPS QR data keys
     */
    protected function get_ips_data_keys(): array {
        return array(
            'K'  => array( 'PR' ),
            'V'  => array( '01' ),
            'C'  => array( '1' ),
            'R'  => array( 'account' ),
            'N'  => array( 'company' ),
            'I'  => array( 'currency', 'total' ),
            'P'  => array( 'customer' ),
            'SF' => array( 'code' ),
            'S'  => array( 'purpose' ),
            'RO' => array( 'model', 'reference' ),
		);
    }

    /**
     * Format the account number
     *
     * @param  string $account The account number.
     * @return string
     */
    protected function format_account( string $account ): string {
        $parts    = explode( '-', $account );
        $parts[1] = str_pad( $parts[1], 13, '0', STR_PAD_LEFT );

        return implode( '', $parts );
    }

    /**
     * Format the total
     *
     * @param  float $total The total.
     * @return string
     */
    protected function format_total( float $total ): string {
        return number_format( $total, 2, ',', '' );
    }

    /**
     * Format the payment model
     *
     * @param  string $model Payment model.
     * @return string
     */
    protected function format_model( string $model ) {
        return empty( $model ) ? '00' : $model;
    }

    /**
     * Format the reference
     *
     * @param  string $reference The reference.
     * @return string
     */
    protected function format_reference( string $reference ): string {
        return str_replace( '-', '', $reference );
    }

    //phpcs:disable
    #endregion
    //phpcs:enable

    //phpcs:disable
    #region QR Creation
    //phpcs:enable

    /**
     * Triggers the QR code generation
     *
     * @param  WC_Order|null $order Order object.
     *
     * @hook     woocommerce_checkout_order_created
     * @type     action
     * @priority 30
     */
    public function add_qr_code_action( WC_Order $order ) {
        $qr_string = $order->get_meta( '_payment_slip_ips_data', true );

        if ( empty( $qr_string ) ) {
            return;
        }

        /**
         * Generate the QR code for the IPS payment slip.
         *
         * @param WC_Order  $order     The order object.
         * @param array     $options   The gateway options.
         *
         * @since 3.3.0
         */
        do_action( 'woocommerce_serbian_generate_ips_qr_code', $order, $this->options );
    }

    /**
     * Get the QR code options
     *
     * @param  array<string, mixed> $options The gateway options.
     * @return array<string, mixed>
     */
    protected function get_qr_code_options( array $options ): array {
        $module_values = array(
            // Finder.
            QRMatrix::M_FINDER_DARK    => $options['qrcode_corner_color'],
            QRMatrix::M_FINDER_DOT     => $options['qrcode_corner_color'],
            QRMatrix::M_FINDER         => $options['qrcode_corner_color'],
            // Aligment.
            QRMatrix::M_ALIGNMENT_DARK => $options['qrcode_color'],
            QRMatrix::M_ALIGNMENT      => $options['qrcode_color'],
            // Timing.
            QRMatrix::M_TIMING_DARK    => $options['qrcode_color'],
            QRMatrix::M_TIMING         => $options['qrcode_color'],
            // Format.
            QRMatrix::M_FORMAT_DARK    => $options['qrcode_color'],
            QRMatrix::M_FORMAT         => $options['qrcode_color'],
            // Version.
            QRMatrix::M_VERSION_DARK   => $options['qrcode_color'],
            QRMatrix::M_VERSION        => $options['qrcode_color'],
            // Data.
            QRMatrix::M_DATA_DARK      => $options['qrcode_color'],
            QRMatrix::M_DATA           => $options['qrcode_color'],
            // Dark module.
            QRMatrix::M_DARKMODULE     => $options['qrcode_color'],
        );

        $logo = $options['qrcode_image'] ? get_option( 'site_icon', 0 ) : 0;
        $args = array(
            'eccLevel'     => EccLevel::L,
            'format'       => 'jpg',
            'moduleValues' => $module_values,
            'scale'        => 15,
            'quality'      => 50,
        );

        if ( $logo ) {
            $args = array_merge(
                $args,
                array(
					'eccLevel'        => EccLevel::H,
					'logo'            => get_attached_file( $logo ),
					'addLogoSpace'    => true,
					'logoSpaceWidth'  => 20,
					'logoSpaceHeight' => 20,
				)
            );
        }

        return $args;
    }

    /**
     * Generates the QR code for the IPS payment slip.
     *
     * @param  WC_Order $order     The order object.
     * @param  array    $options   The gateway options.
     *
     * @hook woocommerce_serbian_generate_ips_qr_code
     * @type action
     */
    public function generate_qr_code( WC_Order $order, array $options, ) {
        QR_Code_Handler::instance()->init( $this->get_qr_code_options( $options ) )->create_file( $order );
    }

    //phpcs:disable
    #endregion
    //phpcs:enable

    /**
     * Show QR Code on the thank you page, and order details.
     *
     * @param  int $order_id The order ID.
     *
     * @hook     woocommerce_thankyou_wcsrb_payment_slip, woocommerce_view_order
     * @type     action
     * @priority 101, 9
     */
    public function show_qr_code( $order_id ) {
        $order = wc_get_order( $order_id );

        if ( 'wcsrb_payment_slip' !== $order->get_payment_method() || ! $this->options['qrcode_shown'] ) {
            return;
        }

        wc_get_template(
            'checkout/payment-slip-qr-code.php',
            $this->get_template_args( $order, 'display' ),
        );
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
     * @priority 55
     */
    public function add_qr_code_to_email( $order, $sent_to_admin, $plain_text, $email ) {
        if (
            'customer_on_hold_order' !== $email->id ||
            $sent_to_admin || $plain_text ||
            'wcsrb_payment_slip' !== $order->get_payment_method()
        ) {
            return;
        }

        $args = $this->get_template_args( $order, 'email' );

        add_action( 'phpmailer_init', fn( &$phpmailer ) => $this->add_inline_image( $phpmailer, $args['path'] ) );

        echo '<div class="woocommerce-email">';
        wc_get_template( 'checkout/payment-slip-qr-code.php', $args );
        echo '</div>';
    }

    /**
     * Get template arguments for the QR Code
     *
     * @param  WC_Order $order The order object.
     * @param  string   $type  The template type. Can be 'display' or 'email'.
     * @return array
     */
    protected function get_template_args( WC_Order $order, string $type ): array {
        $qrc = QR_Code_Handler::instance()->init( $this->get_qr_code_options( $this->options ) );
        return array(
            'src'  => 'email' === $type
                ? 'cid:ips-qr-code'
                : $qrc->get_file_base64( $order ),
            'alt'  => __( 'IPS QR Code', 'serbian-addons-for-woocommerce' ),
            'path' => $qrc->get_filename( $order ),
        );
    }

    /**
     * Adds the QR code as an inline image to the email
     *
     * @param  PHPMailer $phpmailer The PHPMailer object.
     * @param  string    $filepath  The QR code file path.
     */
    protected function add_inline_image( PHPMailer &$phpmailer, string $filepath ) {
        $phpmailer->addEmbeddedImage( $filepath, 'ips-qr-code', 'ips-qr-code.jpg' );
    }
}
