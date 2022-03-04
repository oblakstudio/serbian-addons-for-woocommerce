/* eslint-disable @typescript-eslint/no-var-requires */
const ESLintPlugin = require('eslint-webpack-plugin');
const StyleLintPlugin = require('stylelint-webpack-plugin');

const config = require('./config');

module.exports = {
  plugins: [
    new ESLintPlugin({
      extensions: ['ts'],
      failOnError: true,
      failOnWarning: true,
    }),
    new StyleLintPlugin({
      failOnError: !config.enabled.watcher,
      configFile: `${config.paths.root}/.stylelintrc.js`,
    }),
  ],
}
