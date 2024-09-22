import { WpRouter } from '@wptoolset/router';
import AddressPageController from './controllers/address-page.controller';

jQuery(() => {
  new WpRouter({
    woocommerceCheckout: () => new AddressPageController(),
    woocommerceEditAddress: () => new AddressPageController(),
  }).loadEvents();
});
