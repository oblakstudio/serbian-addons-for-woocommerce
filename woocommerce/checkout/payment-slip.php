<?php //phpcs:disable SlevomatCodingStandard.Functions.RequireMultiLineCall.RequiredMultiLineCall
/**
 * Payment Slip template
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/checkout/payment.php.
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
 * @version 2.0.0
 *
 * @var string $company   Company data.
 * @var string $style     Payment slip style.
 * @var string $model     Payment model.
 * @var string $reference Payment reference.
 * @var string $code      Payment code.
 * @var string $currency  Currency.
 * @var string $account   Account.
 * @var string $customer  Customer data.
 * @var string $purpose   Payment purpose.
 * @var float  $total     Total amount.
 */

defined( 'ABSPATH' ) || exit;

?>
<section class="woocommerce-order-payment-slip <?php echo esc_attr( $style ); ?> ">
    <h2 class="woocommerce-order-details__title">
        <?php esc_html_e( 'Payment instructions', 'serbian-addons-for-woocommerce' ); ?>
    </h2>

    <table class="wcsrb-payment-slip <?php echo esc_attr( $style ); ?>">
        <tbody>
            <tr>
                <!-- BEGIN: Sender/Reciever deets -->
                <td class="slip-section">
                    <table class="slip-section-inner">
                        <tbody>
                            <!-- BEGIN: Sender Deets -->
                            <tr class="top">
                                <td class="block-info">
                                    <span class="block-label">
                                        <?php esc_html_e( 'Sender', 'serbian-addons-for-woocommerce' ); ?>
                                    </span>
                                </td>
                            </tr>
                            <tr>

                                <td class="block-content large">
                                    <?php echo wp_kses_post( $customer ); ?>
                                </td>
                            </tr>
                            <!-- END: Sender Deets -->

                            <!-- BEGIN: Payment Purpose -->
                            <tr>
                                <td class="block-info">
                                    <span class="block-label">
                                        <?php esc_html_e( 'Payment purpose', 'serbian-addons-for-woocommerce' ); ?>
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <td class="block-content large">
                                    <?php echo wp_kses_post( $purpose ); ?>
                                </td>
                            </tr>
                            <!-- END: Payment Purpose -->

                            <!-- BEGIN: Reciever Deets -->
                            <tr>
                                <td class="block-info">
                                    <span class="block-label">
                                        <?php esc_html_e( 'Reciever', 'serbian-addons-for-woocommerce' ); ?>
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <td class="block-content large">
                                    <?php echo wp_kses_post( $company ); ?>
                                </td>
                            </tr>
                            <!-- END: Reciever Deets -->
                        </tbody>
                    </table>

                </td>
                <!-- END: Sender/Reciever deets -->

                <!-- BEGIN: Payment deets -->
                <td class="slip-section last">
                    <table class="slip-section-inner">
                        <tbody>

                            <!-- BEGIN: Currency/Total Deets -->
                            <tr class="top">
                                <td class="block-info code">
                                    <span class="block-label">
                                        <?php esc_html_e( 'Payment code', 'serbian-addons-for-woocommerce' ); ?>
                                    </span>
                                </td>

                                <td class="spacer"></td>

                                <td class="block-info currency">
                                    <span class="block-label">
                                        <?php esc_html_e( 'Currency', 'woocommerce' ); ?>
                                    </span>
                                </td>

                                <td class="spacer"></td>

                                <td class="block-info amount">
                                    <span class="block-label">
                                        <?php esc_html_e( 'Amount', 'serbian-addons-for-woocommerce' ); ?>
                                    </span>
                                </td>

                            </tr>
                            <tr>
                                <td class="block-content small">
                                    <?php echo esc_html( $code ); ?>
                                </td>

                                <td class="spacer"></td>


                                <td class="block-content small">
                                    <?php echo esc_html( $currency ); ?>
                                </td>

                                <td class="spacer"></td>


                                <td class="block-content small">
                                    <?php echo esc_html( number_format( $total, 2, ',', '.' ) ); ?>
                                </td>
                            </tr>
                            <!-- END: Currency/Total Deets -->

                            <!-- BEGIN: Account deets -->
                            <tr>
                                <td class="block-info" colspan="5">
                                    <span class="block-label">
                                        <?php esc_html_e( 'Account payable', 'serbian-addons-for-woocommerce' ); ?>
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <td class="block-content small" colspan="5">
                                    <?php echo esc_html( $account ); ?>
                                </td>
                            </tr>
                            <!-- END: Account deets -->

                            <!-- BEGIN: Model/Reference deets -->
                            <tr>
                                <td class="block-info" colspan="1">
                                    <span class="block-label">
                                        <?php esc_html_e( 'Model', 'serbian-addons-for-woocommerce' ); ?>
                                    </span>
                                </td>

                                <td class="spacer"></td>

                                <td class="block-info" colspan="3">
                                    <span class="block-label">
                                        <?php esc_html_e( 'Payment reference', 'serbian-addons-for-woocommerce' ); ?>
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <td class="block-content small" colspan="1">
                                    <?php echo esc_html( $model ); ?>
                                </td>

                                <td class="spacer"></td>

                                <td class="block-content small" colspan="3">
                                    <?php echo esc_html( $reference ); ?>
                                </td>
                            </tr>
                            <!-- END: Model/Reference deets -->

                        </tbody>
                    </table>




                </td>
                <!-- END: Payment deets -->
            </tr>
        </tbody>
    </table>
</section>
