/*
This is the main WP-Webpack config file.
Each config variable has a short explanation about what it controls
*/
module.exports = {
  /*
  Webpack entry points

  This is a list of 'main' source files we're compiling
  Feel free to rename / remove files you do not need.
  */
  entry: {
    admin: [
      './scripts/admin.ts', // Admin Javascript
      './styles/admin.scss', // Admin CSS
    ],
    main: [
      './scripts/main.ts', // Main Javascript
      './styles/main.scss', // Main CSS
    ],
  },
  /*
  File name format to use for production build

  All webpack merge tags are supported, but we use:
  [name] - filename
  [contenthash] - webpack hash based on the file content

  Default is [name]_[contenthash:5] - filename + 5 character hash. I.e. main_d56fg.css
  */
  cacheBusting: '[name]_[contenthash:5]',

  /*
  External global libraries
  */
  externals: {
    jquery: 'jQuery',
    lodash: '_',
  },

  /*
  Run linters during build process

  Will speed up building, but allows for style and code errors
  */
  lintOnBuild: true,

  /*
  Root path for the asset files.

  If theme it should be: /wp-content/themes/theme-name
  If plugin it should be: /wp-content/plugins/plugin-name
  */
  publicPath: '/wp-content/plugins/serbian-addons-for-woocommerce',

  /*
  Files to watch
  */
  watch: [
    'dist/**/**',
    'templates/**/*.php',
    'template-parts/**/*.php',
    'woocommerce/**/*.php',
    '*.php',
  ],

  devUrl: 'https://srw.ddev.site',

  translation: {
    domain: 'serbian-addons-for-woocommerce',
    filename: 'serbian-addons-for-woocommerce.pot',
    languageDir: './languages',
    bugReport: '',
    translator: 'Author name <author.email@domain.tld>',
    team: 'Team name <team@domain.tld>',
  },
};
