import { WpRouter } from '@wptoolset/router';
import { CompanySettingsController } from './controllers/company-settings.controller';
import { SlipSettingsController } from './controllers/slip-settings.controller';
import { OrderEditController } from './controllers/order-edit.controller';

document.addEventListener('DOMContentLoaded', () =>
  new WpRouter({
    wcsrbOrderEdit: () => new OrderEditController(),
    wcsrbCompanySettings: () => new CompanySettingsController(),
    wcsrbSlipSettings: () => new SlipSettingsController(),
  }).loadEvents(),
);
