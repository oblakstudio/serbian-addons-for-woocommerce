import { sprintf, __ } from '@wordpress/i18n';
import { registerPaymentMethod } from '@woocommerce/blocks-registry';
import { decodeEntities } from '@wordpress/html-entities';

const settings = window.wc.wcSettings.getSetting('wcsrb_payment_slip_data', {});
const label =
  window.wp.htmlEntities.decodeEntities(settings.title) ||
  window.wp.i18n.__('Payment Slip', 'serbian-addons-for-woocommerce');

/**
 * Content component
 */
const Content = () => {
  return decodeEntities(settings.description || '');
};
/**
 * Label component
 *
 * @param {*} props Props from payment API.
 */
const Label = (props) => {
  const { PaymentMethodLabel } = props.components;
  return <PaymentMethodLabel text={label} />;
};

const WCSRB_Payment_Slip_Gateway = {
  name: 'wcsrb_payment_slip',
  label: <Label />,
  content: <Content />,
  edit: <Content />,
  canMakePayment: () => true,
  ariaLabel: label,
  supports: {
    features: settings.supports,
  },
};

registerPaymentMethod(WCSRB_Payment_Slip_Gateway);
