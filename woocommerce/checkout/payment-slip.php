<?php
/**
 * Responsive Payment Slip template (Thin Lines, Left Word Wrap)
 *
 * @package Serbian Addons for WooCommerce
 * @subpackage Templates
 * @version 2.1.2
 *
 * @var string $company
 * @var string $style
 * @var string $model
 * @var string $reference
 * @var string $code
 * @var string $currency
 * @var string $account
 * @var string $customer
 * @var string $purpose
 * @var float  $total
 */

defined( 'ABSPATH' ) || exit;
?>
<section class="woocommerce-order-payment-slip <?php echo esc_attr( $style ); ?>" style="font-family: Arial, sans-serif; max-width:600px; margin:auto; padding:10px;">
    <h2 style="text-align:center; font-size:18px; margin-bottom:20px;">
        <?php esc_html_e( 'Uputstvo za plaćanje preko računa', 'serbian-addons-for-woocommerce' ); ?>
    </h2>

    <table width="100%" cellpadding="0" cellspacing="0" border="0" style="border-collapse: collapse; table-layout: fixed; width:100%; font-family: Arial, sans-serif;">
        <!-- Sender -->
        <tr style="border-bottom:1px solid #ccc;">
            <td style="padding:10px; width:30%; font-weight:bold; word-wrap: break-word; text-align:left;">
                <?php esc_html_e( 'Pošiljalac', 'serbian-addons-for-woocommerce' ); ?>
            </td>
            <td style="padding:10px;">
                <?php echo wp_kses_post( $customer ); ?>
            </td>
        </tr>

        <!-- Payment Purpose -->
        <tr style="border-bottom:1px solid #ccc;">
            <td style="padding:10px; font-weight:bold; word-wrap: break-word; text-align:left;">
                <?php esc_html_e( 'Svrha uplate', 'serbian-addons-for-woocommerce' ); ?>
            </td>
            <td style="padding:10px;">
                <?php echo wp_kses_post( $purpose ); ?>
            </td>
        </tr>

        <!-- Receiver -->
        <tr style="border-bottom:1px solid #ccc;">
            <td style="padding:10px; font-weight:bold; word-wrap: break-word; text-align:left;">
                <?php esc_html_e( 'Primalac', 'serbian-addons-for-woocommerce' ); ?>
            </td>
            <td style="padding:10px;">
                <?php echo wp_kses_post( $company ); ?>
            </td>
        </tr>

     <!-- Payment Details -->
<tr style="border-bottom:1px solid #ccc;">
    <td style="padding:10px; width:30%; font-weight:bold; word-wrap: break-word; text-align:left;">
        <?php esc_html_e( 'Šifra uplate', 'serbian-addons-for-woocommerce' ); ?>
    </td>
    <td style="padding:10px;">
        <?php echo esc_html( $code ); ?>
    </td>
</tr>
<tr style="border-bottom:1px solid #ccc;">
    <td style="padding:10px; font-weight:bold; word-wrap: break-word; text-align:left;">
        <?php esc_html_e( 'Valuta', 'serbian-addons-for-woocommerce' ); ?>
    </td>
    <td style="padding:10px;">
        <?php echo esc_html( $currency ); ?>
    </td>
</tr>
<tr style="border-bottom:1px solid #ccc;">
    <td style="padding:10px; font-weight:bold; word-wrap: break-word; text-align:left;">
        <?php esc_html_e( 'Iznos', 'serbian-addons-for-woocommerce' ); ?>
    </td>
    <td style="padding:10px;">
        <?php echo esc_html( number_format( $total, 2, ',', '.' ) ); ?>
    </td>
</tr>
<tr style="border-bottom:1px solid #ccc;">
    <td style="padding:10px; font-weight:bold; word-wrap: break-word; text-align:left;">
        <?php esc_html_e( 'Račun za uplatu', 'serbian-addons-for-woocommerce' ); ?>
    </td>
    <td style="padding:10px;">
        <?php echo esc_html( $account ); ?>
    </td>
</tr>
<tr style="border-bottom:1px solid #ccc;">
    <td style="padding:10px; font-weight:bold; word-wrap: break-word; text-align:left;">
        <?php esc_html_e( 'Model', 'serbian-addons-for-woocommerce' ); ?>
    </td>
    <td style="padding:10px;">
        <?php echo esc_html( $model ); ?>
    </td>
</tr>
<tr>
    <td style="padding:10px; font-weight:bold; word-wrap: break-word; text-align:left;">
        <?php esc_html_e( 'Poziv na broj', 'serbian-addons-for-woocommerce' ); ?>
    </td>
    <td style="padding:10px;">
        <?php echo esc_html( $reference ); ?>
    </td>
</tr>

    </table>

    <!-- Print-friendly style -->
    <style>
        @media print {
            body * { visibility: hidden; }
            .woocommerce-order-payment-slip, .woocommerce-order-payment-slip * { visibility: visible; }
            .woocommerce-order-payment-slip { position: absolute; top: 0; left: 0; width: 100%; }
        }

        /* Mobile-friendly padding */
        @media screen and (max-width:480px) {
            .woocommerce-order-payment-slip td { padding:12px !important; }
        }
    </style>
</section>
