import { WpRouter } from '@wptoolset/router';
import { CompanySettingsController } from './routes/company-settings.controller';
import { SlipSettingsController } from './routes/slip-settings.controller';

const routes = new WpRouter({
  wcsrbCompanySettings: () => new CompanySettingsController(),
  wcsrbSlipSettings: () => new SlipSettingsController(),
});

document.addEventListener('DOMContentLoaded', () => routes.loadEvents());
