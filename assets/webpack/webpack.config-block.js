const path = require('path');
const defaultConfig = require('@wordpress/scripts/config/webpack.config');
const WooCommerceDependencyExtractionWebpackPlugin = require('@woocommerce/dependency-extraction-webpack-plugin');

// Remove SASS rule from the default config so we can define our own.
const defaultRules = defaultConfig.module.rules.filter((rule) => {
  return String(rule.test) !== String(/\.(sc|sa)ss$/);
});

module.exports = {
  ...defaultConfig,
  context: path.resolve(process.cwd(), 'assets', 'blocks'),
  entry: {
    'payment-slip-block': './payment-slip/payment-method-wcsrb-slip.js',
  },
  output: {
    path: path.resolve(process.cwd(), 'dist', 'blocks'),
    filename: '[name]/block.js',
  },
  module: {
    ...defaultConfig.module,
    rules: [...defaultRules],
  },
  plugins: [
    ...defaultConfig.plugins.filter((plugin) => plugin.constructor.name !== 'DependencyExtractionWebpackPlugin'),
    new WooCommerceDependencyExtractionWebpackPlugin(),
    // new MiniCssExtractPlugin({
    //   filename: `[name].css`,
    // }),
  ],
};
