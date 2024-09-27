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

config.plugins[0] = [
  '@semantic-release/commit-analyzer',
  {
    preset: 'angular',
    releaseRules: [
      { type: 'compat', release: 'patch' },
      { type: 'refactor', release: 'patch' },
      { type: 'style', release: 'patch' },
    ],
    parserOpts: {
      noteKeywords: ['BREAKING CHANGE', 'BREAKING CHANGES'],
    },
  },
];
config.plugins[1] = [
  '@semantic-release/release-notes-generator',
  {
    preset: 'angular',
    presetConfig: {
      types: [
        {
          tag: 'compat',
          section: 'Compatibility',
          hidden: false,
        },
        {
          tag: 'refactor',
          section: 'Refactor',
          hidden: false,
        },
        {
          tag: 'style',
          section: 'Code style',
          hidden: false,
        },
      ],
    },
  },
];

module.exports = config;
