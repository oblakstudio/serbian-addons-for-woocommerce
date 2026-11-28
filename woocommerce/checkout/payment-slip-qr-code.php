<?php
/**
 * Stacked QR Code Payment Slip Template (Web + Email)
 *
 * @package Serbian Addons for WooCommerce
 * @subpackage Templates
 * @version 2.8.0
 * @var string $alt QR Code image alt.
 * @var string $src QR Code image.
 */

defined( 'ABSPATH' ) || exit;
?>

<?php if ( '' !== $src ) : ?>
<section class="woocommerce-order-ips-qr-code" style="font-family: Arial, sans-serif; max-width:600px; margin:auto; padding:10px;">
    <h2 style="text-align:center; font-size:18px; margin-bottom:15px;">
        <?php esc_html_e( 'Instant payment', 'serbian-addons-for-woocommerce' ); ?>
    </h2>

    <!-- Outer table for stacking -->
    <table width="100%" cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse; table-layout:fixed;">

        <!-- Description header -->
        <tr>
            <td style="background-color:#f0f0f0; padding:8px; font-weight:bold;">
                <?php esc_html_e( 'Description', 'serbian-addons-for-woocommerce' ); ?>
            </td>
        </tr>

        <!-- Description content -->
        <tr>
            <td style="padding:8px;">
                <p style="margin:5px 0;"><?php esc_html_e( 'The NBS IPS QR code is an innovative way to perform instant payments using mobile devices.', 'serbian-addons-for-woocommerce' ); ?></p>
                <p style="margin:5px 0;"><?php esc_html_e( 'Security is guaranteed by the standards of the National Bank of Serbia.', 'serbian-addons-for-woocommerce' ); ?></p>
            </td>
        </tr>

        <!-- QR Code image -->
        <tr>
            <td style="text-align:center; padding:10px;">
                <img src="<?php echo esc_attr( $src ); ?>" alt="<?php echo esc_attr( $alt ); ?>" style="width:150px; max-width:100%; height:auto; display:block; margin:auto;">
            </td>
        </tr>

        <!-- How to pay header -->
        <tr>
            <td style="background-color:#f0f0f0; padding:8px; font-weight:bold;">
                <?php esc_html_e( 'How to pay', 'serbian-addons-for-woocommerce' ); ?>
            </td>
        </tr>

        <!-- How to pay instructions -->
        <tr>
            <td style="padding:8px;">
                <ol style="margin:5px 0; padding-left:20px;">
                    <li><?php esc_html_e( 'Select IPS SCAN in the m-banking app', 'serbian-addons-for-woocommerce' ); ?></li>
                    <li><?php esc_html_e( 'Scan the QR code', 'serbian-addons-for-woocommerce' ); ?></li>
                    <li><?php esc_html_e( 'Confirm with your PIN or fingerprint', 'serbian-addons-for-woocommerce' ); ?></li>
                    <li><?php esc_html_e( 'Payment is complete', 'serbian-addons-for-woocommerce' ); ?></li>
                </ol>
            </td>
        </tr>
    </table>

    <!-- Print-friendly styles -->
    <style>
        @media print {
            body * { visibility: hidden; }
            .woocommerce-order-ips-qr-code, .woocommerce-order-ips-qr-code * { visibility: visible; }
            .woocommerce-order-ips-qr-code { position:absolute; top:0; left:0; width:100%; }
        }
    </style>
</section>
<?php endif; ?>
