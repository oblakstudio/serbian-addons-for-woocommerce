<?php
namespace Oblak\WCRS\CustomerType;

use function Oblak\validateMB;
use function Oblak\validatePIB;

class FieldValidation {

    private static $fields_to_check = ['billing_company', 'billing_mb', 'billing_pib'];

    public function __construct() {
        add_filter('woocommerce_after_save_address_validation', [$this, 'saveAddressOverride'], 99, 2);
        add_filter('woocommerce_after_checkout_validation', [$this, 'checkoutFieldsOverride'], 99, 2);
    }

    /**
     * Adds custom validation to billing address field saving
     *
     * @param  int    $user_id      Current User ID
     * @param  string $load_address Address type being edited
     * @return void
     */
    public function saveAddressOverride($user_id, $load_address) {
        if ($load_address == 'shipping') {
            return;
        }

        $notices = WC()->session->get('wc_notices', []);

        if (array_key_exists('error', $notices) && is_array($notices['error'])) {
            foreach ($notices['error'] as $index => $notice) {
                if (in_array($notice['data']['id'], self::$fields_to_check)) {
                    unset($notices['error'][$index]);
                }
            }
        }

        if ($_POST['billing_type'] == 'person') {
            WC()->session->set('wc_notices', $notices);
            return;
        }

        if (!array_key_exists('error', $notices)) {
            $notices['error'] = [];
        }

        if (!validateMB($_POST['billing_mb'])) {
            $notices['error'][] = [
                'notice' => __('Company number is invalid', 'serbian-addons-for-woocommerce'),
                'data'    => [
                    'id' => 'billing_mb',
                ],
            ];
        }

        if (!validatePIB($_POST['billing_pib'])) {
            $notices['error'][] = [
                'notice' => __('Company Tax Number is invalid', 'serbian-addons-for-woocommerce'),
                'data'    => [
                    'id' => 'billing_pib',
                ],
            ];
        }

        WC()->session->set('wc_notices', $notices);
    }

    /**
     * Adds custom validation to billing fields during checkout
     *
     * @param  array $data
     * @param  WP_Error $errors
     * @return void
     */
    public function checkoutFieldsOverride($data, $errors) {

        foreach (self::$fields_to_check as $field) {
            if (!empty($errors->get_all_error_data($field . '_required'))) {
                $errors->remove($field . '_required');
            }
        }

        if ($data['billing_type'] == 'company' && 'RS' === $data['billing_country']) {
            if ($data['billing_company'] == '') {
                $errors->add('billing_company_required', __('Company name is required', 'serbian-addons-for-woocommerce'));
            }
            if (!validateMB($data['billing_mb'])) {
                $errors->add('billing_mb_required', __('Company number is invalid', 'serbian-addons-for-woocommerce'));
            }

            if (!validatePIB($data['billing_pib'])) {
                $errors->add('billing_pib_required', __('Company Tax Number is invalid', 'serbian-addons-for-woocommerce'));
            }
        }
    }
}
