/* eslint-disable @typescript-eslint/no-var-requires */
/**
 * Block checkout scripts (payment methods), built with @wordpress/scripts.
 *
 * WooCommerce's dependency extraction plugin replaces the WordPress one, so `@woocommerce/*`
 * imports become script dependencies too. Each entry gets a generated `index.asset.php`
 * (dependencies + content hash) that the PHP integration reads.
 */
const path = require('path');
const defaultConfig = require('@wordpress/scripts/config/webpack.config');
const WooCommerceDependencyExtractionWebpackPlugin = require('@woocommerce/dependency-extraction-webpack-plugin');

module.exports = {
  ...defaultConfig,
  entry: {
    'payment-slip': path.resolve(process.cwd(), 'assets/blocks/payment-slip/index.js'),
  },
  output: {
    ...defaultConfig.output,
    path: path.resolve(process.cwd(), 'dist/blocks'),
    filename: '[name]/index.js',
  },
  plugins: [
    ...defaultConfig.plugins.filter((plugin) => plugin.constructor.name !== 'DependencyExtractionWebpackPlugin'),
    new WooCommerceDependencyExtractionWebpackPlugin(),
  ],
};
