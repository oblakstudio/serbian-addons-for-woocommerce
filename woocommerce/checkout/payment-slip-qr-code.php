<?php
/**
 * Payment Slip template
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/checkout/payment-slip-qr-code.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://docs.woocommerce.com/document/template-structure/
 * @package Serbian Addons for WooCommerce
 * @subpackage Templates
 * @version 2.3.0
 *
 * @var array  $qr_code_html QR Code HTML.
 * @var string $qr_code_img  QR Code image.
 */

defined( 'ABSPATH' ) || exit;
?>

<section class="woocommerce-order-ips-qr-code">
    <h2><?php esc_html_e( 'Instant payment', 'serbian-addons-for-woocommerce' ); ?></h2>
    <table class="qr-code">
        <tbody>
            <tr>
                <td class="qr-code-wrap">
                    <div class="qr-code-holder">
                        <img src="<?php echo esc_attr( $src ); ?>" alt="<?php echo esc_attr( $alt ); ?>">
                    </div>
                </td>
                <td class="qr-code-desc">
                    <p>
                        <?php esc_html_e( 'The NBS IPS QR code is an innovative way to perform instant payments using mobile devices.', 'serbian-addons-for-woocommerce' ); ?>
                    </p>
                    <p>
                        <?php esc_html_e( ' Security is guaranteed by the standards of the National Bank of Serbia.', 'serbian-addons-for-woocommerce' ); ?>
                    </p>
                    <h3>
                        <?php esc_html_e( 'How to pay', 'serbian-addons-for-woocommerce' ); ?>
                    </h3>
                    <ol>
                        <li>
                            <?php esc_html_e( 'Select IPS SCAN in the m-banking app', 'serbian-addons-for-woocommerce' ); ?>
                        </li>
                        <li>
                            <?php esc_html_e( 'Scan the QR code', 'serbian-addons-for-woocommerce' ); ?>
                        </li>
                        <li>
                            <?php esc_html_e( 'Confirm with your PIN or fingerprint', 'serbian-addons-for-woocommerce' ); ?>
                        </li>
                        <li>
                            <?php esc_html_e( 'Payment is complete', 'serbian-addons-for-woocommerce' ); ?>
                        </li>
                    </ol>
                </td>
            </tr>
        </tbody>
    </table>
</section>
