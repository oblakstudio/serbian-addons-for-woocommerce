/* eslint-disable @typescript-eslint/no-var-requires */
const path = require('path');
const { argv } = require('yargs');
const { merge } = require('webpack-merge');

// eslint-disable-next-line import/no-dynamic-require
const userConfig = require('../../wpwp.config.js');
const nodeVersion = process.versions.node.split('.')[0];
const isProduction = !!((argv.env && argv.env == 'production') || argv.p);
const rootPath = userConfig.paths && userConfig.paths.root ? userConfig.paths.root : process.cwd();

const wpwpConfig = merge(
  {
    useFibers: nodeVersion != 16 ? true : false,
    open: true,
    copy: 'images/**/*',
    cacheBusting: userConfig.cacheBusting,
    externals: userConfig.externals,
    paths: {
      root: rootPath,
      assets: path.join(rootPath, 'assets'),
      dist: path.join(rootPath, 'dist'),
    },
    enabled: {
      sourceMaps: true, //!isProduction,
      optimize: isProduction,
      cacheBusting: isProduction,
      watcher: !!argv.watch,
      linter: userConfig.lintOnBuild,
    },
    watch: [],
  },
  userConfig,
);

module.exports = merge(wpwpConfig, {
  env: Object.assign({ production: isProduction, development: !isProduction }, argv.env),
  publicPath: `${wpwpConfig.publicPath}/${path.basename(wpwpConfig.paths.dist)}/`,
  manifest: {},
});

if (process.env.NODE_ENV === undefined) {
  process.env.NODE_ENV = isProduction ? 'production' : 'development';
}
