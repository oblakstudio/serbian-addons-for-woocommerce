import { WpRouter } from '@wptoolset/router';
import { CompanySettingsController } from './controllers/company-settings.controller';
import { SlipSettingsController } from './controllers/slip-settings.controller';

document.addEventListener('DOMContentLoaded', () =>
  new WpRouter({
    wcsrbCompanySettings: () => new CompanySettingsController(),
    wcsrbSlipSettings: () => new SlipSettingsController(),
  }).loadEvents(),
);
