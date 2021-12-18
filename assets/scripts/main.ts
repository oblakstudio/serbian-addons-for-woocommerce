import { WpRouter } from '@wptoolset/router';
import Checkout from './routes/checkout.resolver';
import { EditAddress } from './routes/edit-address.resolver';

const routes = new WpRouter({
  woocommerceCheckout: () => new Checkout(),
  woocommerceEditAddress: () => new EditAddress(),
});

jQuery(() => {
  routes.loadEvents();
});
