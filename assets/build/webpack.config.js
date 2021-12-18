/* eslint-disable @typescript-eslint/no-var-requires */
'use strict';

const { merge } = require('webpack-merge');
const { CleanWebpackPlugin } = require('clean-webpack-plugin');
const CopyPlugin = require('copy-webpack-plugin');

const MiniCssExtractPlugin = require('mini-css-extract-plugin');
const FriendlyErrorsWebpackPlugin = require('@soda/friendly-errors-webpack-plugin');

const config = require('./config');

const assetsFilenames = config.enabled.cacheBusting ? config.cacheBusting : '[name]';

let webpackConfig = {
  stats: false,
  devtool: config.enabled.sourceMaps ? 'cheap-source-map' : false,
  context: config.paths.assets,
  entry: config.entry,
  cache: {
    type: 'filesystem',
  },
  externals: config.externals,
  output: {
    path: config.paths.dist,
    publicPath: config.publicPath,
    filename: `scripts/${assetsFilenames}.js`,
  },
  module: {
    rules: [
      {
        test: /\.ts$/,
        exclude: [/node_modules(?![/|\\](bootstrap|foundation-sites))/],
        use: ['babel-loader'],
      },
      {
        test: /\.scss$/,
        use: [
          MiniCssExtractPlugin.loader,
          {
            loader: 'css-loader',
            options: {
              importLoaders: 2,
            },
          },
          {
            loader: 'postcss-loader',
            options: {
              postcssOptions: {
                config: require.resolve('./postcss.config.js'),
              },
              sourceMap: config.enabled.sourceMaps,
            },
          },
          {
            loader: 'resolve-url-loader',
            options: {
              sourceMap: config.enabled.sourceMaps,
            }
          },
          {
            loader: 'sass-loader',
            options: {
              sourceMap: config.enabled.sourceMaps,
              implementation: require('sass'),
              sassOptions: {
                fiber: false,
              },
            },
          },
        ],
      },
      {
        test: /\.(png|svg|jpg|jpeg|gif|ico)$/i,
        type: 'asset/resource',
        generator: {
          filename: `images/${assetsFilenames}[ext]`,
        },
      },
      {
        test: /\.(ttf|otf|eot|woff2?)$/,
        type: 'asset/resource',
        generator: {
          filename: `fonts/${assetsFilenames}[ext]`,
        },
      },
    ],
  },

  plugins: [
    new CopyPlugin({
      patterns: [
        {
          from: 'images/**',
          to: `images/${assetsFilenames}[ext]`,
          force: false,
          noErrorOnMissing: true,
        },
      ],
    }),
    new MiniCssExtractPlugin({
      filename: `styles/${assetsFilenames}.css`,
    }),
    new CleanWebpackPlugin({
      verbose: false,
      cleanStaleWebpackAssets: true,
    }),
    new FriendlyErrorsWebpackPlugin(),
  ],
  resolve: {
    extensions: ['.ts', '.scss', '.css', '.js'],
  },
};

// Enable linting during build
if (config.enabled.linter) {
  webpackConfig = merge(webpackConfig, require('./webpack.config.lint'));
}

// Enable optimizations for production builds
if (config.enabled.optimize) {
  webpackConfig = merge(webpackConfig, require('./webpack.config.optimize'));
}

if (config.enabled.watcher) {
  webpackConfig = merge(webpackConfig, require('./webpack.config.watch'));
}

// Production manifest
if (config.enabled.cacheBusting) {
  const WebpackAssetsManifest = require('webpack-assets-manifest');

  webpackConfig.plugins.push(
    new WebpackAssetsManifest({
      output: 'assets.json',
      space: 2,
      writeToDisk: false,
      assets: config.manifest,
      replacer: require('./util/assetManifestsFormatter'),
    }),
  );
}

module.exports = webpackConfig;
