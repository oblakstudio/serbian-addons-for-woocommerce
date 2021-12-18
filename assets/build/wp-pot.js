/* eslint-disable @typescript-eslint/no-var-requires */
const path  = require('path');
const wpPot = require('wp-pot');

const config = require('../../wpwp.config.js');

const wpPotOpts = {
  destFile: path.resolve(
    config.translation.languageDir,
    config.translation.filename
  ),
  domain: config.translation.domain,
  src: path.resolve('**/*.php'),
  bugReport: config.translation.bugReport,
  lastTranslator: config.translation.translator,
  team: config.translation.team,
}

wpPot(wpPotOpts);
