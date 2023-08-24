import { WpRouter } from '@wptoolset/router';
import { CompanySettingsController } from './routes/company-settings.controller';

const routes = new WpRouter({
  wcsrbCompanySettings: () => new CompanySettingsController(),
});

document.addEventListener('DOMContentLoaded', () => routes.loadEvents());
