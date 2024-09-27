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
    preset: 'conventionalcommits',
    releaseRules: [
      { type: 'chore', release: false },
      { type: 'perf', release: 'patch' },
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
    preset: 'conventionalcommits',
    presetConfig: {
      types: [
        {
          type: 'feat',
          section: ':sparkles: Features',
          hidden: false,
        },
        {
          type: 'fix',
          section: ':bug: Bug Fixes',
          hidden: false,
        },
        {
          type: 'compat',
          section: ':gear: Compatibility',
          hidden: false,
        },
        {
          type: 'refactor',
          section: ':recycle: Refactor',
          hidden: false,
        },
        {
          type: 'style',
          section: ':art: Code style',
          hidden: false,
        },
        {
          type: 'perf',
          section: ':rocket: Performance',
          hidden: false,
        },
        {
          type: 'chore',
          section: ':wrench: Maintenance',
          hidden: false,
        },
      ],
    },
  },
];

module.exports = config;
