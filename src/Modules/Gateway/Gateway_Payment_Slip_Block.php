<?php
/**
 * Gateway_Payment_Slip_Block class file.
 *
 * @package Serbian Addons for WooCommerce
 * @subpackage Gateway
 */

namespace Oblak\WCSRB\Gateway;

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

/**
 * Payment Slip Block.
 *
 * @since 4.0.0
 */
class Gateway_Payment_Slip_Block extends AbstractPaymentMethodType {
    /**
     * Gateway name
     *
     * @var string
     */
    protected $name = Gateway_Payment_Slip::GW_ID;

    /**
     * Constructor
     *
     * @param Gateway_Payment_Slip $gw Gateway instance.
     */
    public function __construct( private Gateway_Payment_Slip $gw ) {
    }

    /**
     * When called invokes any initialization/setup for the integration.
     */
    public function initialize() {
        $this->settings = \get_option( $this->gw->get_option_key() );
    }

    /**
     * Returns if this payment method should be active. If false, the scripts will not be enqueued.
     *
     * @return bool
     */
    public function is_active() {
        return $this->gw->is_available();
    }

    /**
     * Returns the supported features of this payment method.
     *
     * @return array<string,string>
     */
    public function get_payment_method_data() {
        return array(
            'description' => $this->gw->get_description(),
            'supports'    => $this->get_supported_features(),
            'title'       => $this->gw->get_title(),
        );
    }

    /**
     * Returns the script handles for the payment method.
     *
     * @return array<string>
     */
    public function get_payment_method_script_handles() {
        \wp_register_script(
            'wc-payment-method-wcsrb-slip',
            \plugins_url( 'dist/blocks/payment-slip-block/block.js', WCSRB_BASE ),
            array(
                'wc-blocks-registry',
                'wc-settings',
                'wp-element',
                'wp-html-entities',
                'wp-i18n',
            ),
            WCSRB_VER,
            true,
        );

        return array( 'wc-payment-method-wcsrb-slip' );
    }
}
