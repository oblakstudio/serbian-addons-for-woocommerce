/**
 * Payment slip as a block checkout payment method.
 *
 * Settings come from Gateway_Payment_Slip_Block::get_payment_method_data().
 * createElement instead of JSX: the automatic JSX runtime needs the `react-jsx-runtime`
 * script, which only WordPress 6.6+ registers.
 */
import { registerPaymentMethod } from '@woocommerce/blocks-registry';
import { getSetting } from '@woocommerce/settings';
import { createElement } from '@wordpress/element';
import { decodeEntities } from '@wordpress/html-entities';
import { __ } from '@wordpress/i18n';

const settings = getSetting('wcsrb_payment_slip_data', {});
const title = decodeEntities(settings.title || '') || __('Payment Slip', 'serbian-addons-for-woocommerce');

const Content = () => decodeEntities(settings.description || '');

const Label = ({ components: { PaymentMethodLabel } }) => createElement(PaymentMethodLabel, { text: title });

registerPaymentMethod({
  name: 'wcsrb_payment_slip',
  label: createElement(Label),
  content: createElement(Content),
  edit: createElement(Content),
  canMakePayment: () => true,
  ariaLabel: title,
  supports: {
    features: settings.supports ?? ['products'],
  },
});
