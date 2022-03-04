/* eslint-disable @typescript-eslint/no-var-requires */
const webpack = require('webpack');
const BrowserSyncPlugin = require('browser-sync-webpack-plugin');

const config = require('../../wpwp.config.js');

module.exports = {
  output: {
    pathinfo: true,
    publicPath: `${config.devUrl}${config.publicPath}/dist/`,
  },
  devtool: 'cheap-module-source-map',
  plugins: [
    new webpack.HotModuleReplacementPlugin(),
    new BrowserSyncPlugin(
      {
        host: 'localhost',
        port: 3000,
        proxy: config.devUrl,
        injectCss: true,
        files: config.watch,
      },
      {
        reload: false,
      },
    ),
  ],
};
