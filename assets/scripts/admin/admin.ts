import { WpRouter } from '@wptoolset/router';
import { SlipSettingsController } from './controllers/slip-settings.controller';
import { OrderEditController } from './controllers/order-edit.controller';

document.addEventListener('DOMContentLoaded', () =>
  new WpRouter({
    wcsrbOrderEdit: () => new OrderEditController(),
    wcsrbSlipSettings: () => new SlipSettingsController(),
  }).loadEvents(),
);
