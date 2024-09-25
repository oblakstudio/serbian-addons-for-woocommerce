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

    //phpcs:disable Squiz.Commenting.InlineComment.WrongStyle
    #region IPS Data
    //phpcs:enable Squiz.Commenting.InlineComment.WrongStyle

    /**
     * Adds payment slip metadata to the order
     *
     * @param  int      $order_id Order ID.
     * @param  WC_Order $order    Order object.
     */
    #[Action( tag: 'woocommerce_new_order', priority: 20 )]
    public function add_ips_metadata( int $order_id, WC_Order $order ) {
        $ips_fmtd_arr = array();

        foreach ( $this->format_ips_data( $order ) as $key => $value ) {
            $ips_fmtd_arr[] = \sprintf( '%s:%s', $key, $value );
        }

        if ( ! $ips_fmtd_arr ) {
            return;
        }

        $order->update_meta_data( '_payment_slip_ips_data', \implode( '|', $ips_fmtd_arr ) );
        $order->save();
    }

    /**
     * Triggers the QR code generation
     *
     * @param  int      $order_id Order ID.
     * @param  WC_Order $order    Order object.
     */
    #[Action( tag: 'woocommerce_new_order', priority: 30 )]
    public function add_qr_code_action( int $order_id, WC_Order $order ) {
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
        \do_action( 'woocommerce_serbian_generate_ips_qr_code', $order, $this->options );
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
        $filename = QR_Code_Handler::instance()->get_filename( \wc_get_order( $order_id ) );

        if ( ! $filename || ! \xwp_wpfs()->exists( $filename ) ) {
            return;
        }

        \xwp_wpfs()->delete( $filename );
    }

    /**
     * Adds payment slip metadata to the order
     *
     * @param  WC_Order|null $order Order object.
     */
    public function format_ips_data( $order ): array {
        $slip_data = $order->get_meta( '_payment_slip_data', true );

        if ( ! $slip_data ) {
            return array();
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

            $qr_data[ $key ] = $value;
        }

        return $qr_data;
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
        $parts    = \explode( '-', $account );
        $parts[1] = \str_pad( $parts[1], 13, '0', STR_PAD_LEFT );

        return \implode( '', $parts );
    }

    /**
     * Format the company data
     *
     * @param  string $company The company data.
     * @return string
     */
    protected function format_company( string $company ): string {
        return $this->shorten_tag( $company );
    }

    /**
     * Format the customer data
     *
     * @param  string $customer The customer data.
     * @return string
     */
    protected function format_customer( string $customer ): string {
        return $this->shorten_tag( $customer );
    }

    /**
     * Shortens the tag to 70 characters
     *
     * @param  string $tag The tag.
     * @return string
     */
    protected function shorten_tag( string $tag ): string {
        $tag    = preg_replace( '/<br\/?>/', "\n", $tag );
        $count  = 1;
        $length = strlen( $tag );

        while ( 70 < $length && $count > 0 ) {
            $tag    = preg_replace( "/\n.*$/", '', $tag, -1, $count );
            $length = strlen( $tag );
        }

        if ( 70 < $length && 0 === $count ) {
            $tag = substr( $tag, 0, 70 );
        }

        return $tag;
    }

    /**
     * Format the total
     *
     * @param  float $total The total.
     * @return string
     */
    protected function format_total( float $total ): string {
        return \number_format( $total, 2, ',', '' );
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
        return \str_replace( '-', '', $reference );
    }

    //phpcs:disable Squiz.Commenting.InlineComment.WrongStyle
    #endregion
    //phpcs:enable Squiz.Commenting.InlineComment.WrongStyle

    //phpcs:disable Squiz.Commenting.InlineComment.WrongStyle
    #region QR Creation
    //phpcs:enable Squiz.Commenting.InlineComment.WrongStyle

    /**
     * Get the QR code options
     *
     * @param  array<string, mixed> $options The gateway options.
     * @return array<string, mixed>
     */
    protected function get_qr_code_options( array $options ): array {
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
     * Generates the QR code for the IPS payment slip.
     *
     * @param  WC_Order $order     The order object.
     * @param  array    $options   The gateway options.
     */
    #[Action( tag: 'woocommerce_serbian_generate_ips_qr_code' )]
    public function generate_qr_code( WC_Order $order, array $options, ) {
        QR_Code_Handler::instance()->init( $this->get_qr_code_options( $options ) )->create_file( $order );
    }

    //phpcs:disable Squiz.Commenting.InlineComment.WrongStyle
    #endregion
    //phpcs:enable Squiz.Commenting.InlineComment.WrongStyle

    /**
     * Show QR Code on the thank you page, and order details.
     *
     * @param  int $order_id The order ID.
     */
    #[Action( tag: 'woocommerce_thankyou_wcsrb_payment_slip', priority: 101 )]
    #[Action( tag: 'woocommerce_view_order', priority: 9 )]
    public function show_qr_code( $order_id ) {
        $order = \wc_get_order( $order_id );

        if ( 'wcsrb_payment_slip' !== $order->get_payment_method() || ! $this->options['qrcode_shown'] || $order->is_paid() ) {
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
            'wcsrb_payment_slip' !== $order->get_payment_method()
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
    protected function get_template_args( WC_Order $order, string $type ): array {
        $qrc = QR_Code_Handler::instance()->init( $this->get_qr_code_options( $this->options ) );
        return array(
            'alt'  => \__( 'IPS QR Code', 'serbian-addons-for-woocommerce' ),
            'path' => $qrc->get_filename( $order ),
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
    protected function add_inline_image( PHPMailer &$phpmailer, string $filepath ) {
        $phpmailer->addEmbeddedImage( $filepath, 'ips-qr-code', 'ips-qr-code.jpg' );
    }
}
