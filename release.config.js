/* eslint-disable @typescript-eslint/no-var-requires */
const generateConfig = require('@x-wp/semantic-release-config').default;

const config = generateConfig({
  branches: ['master', { name: 'beta', prerelease: true }],
  type: 'plugin',
  name: 'Serbian Addons for WooCommerce',
  slug: 'serbian-addons-for-woocommerce',
  wp: {
    withVersionFile: true,
    withAssets: true,
    withReadme: true,
  },
});

config.plugins.push([
  '@semantic-release/commit-analyzer',
  {
    preset: 'angular',
    releaseRules: [
      { type: 'refactor', release: 'patch' },
      { type: 'style', release: 'patch' },
    ],
    parserOpts: {
      noteKeywords: ['BREAKING CHANGE', 'BREAKING CHANGES'],
    },
  },
]);

module.exports = config;
