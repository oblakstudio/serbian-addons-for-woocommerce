import { WpRouter } from '@wptoolset/router';
import AddressPageController from './controllers/address-page.controller';
import { BlockCheckoutController } from './controllers/block-checkout.controller';

jQuery(() => {
  new WpRouter({
    wcBlockCheckout: () => new BlockCheckoutController(),
    wcClassicCheckout: () => new AddressPageController(),
    woocommerceEditAddress: () => new AddressPageController(),
  }).loadEvents();
});
