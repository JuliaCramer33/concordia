const path = require('path');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');

module.exports = {
  entry: {
    'gfcui-dependent-programs': path.resolve(__dirname, 'src/js/gfcui-dependent-programs.js'),
    'gfcui-start-terms': path.resolve(__dirname, 'src/js/gfcui-start-terms.js'),
    'gfcui-styles': path.resolve(__dirname, 'src/scss/style.scss'),
  },
  output: {
    filename: '[name].js',
    path: path.resolve(__dirname, 'assets/dist'),
    publicPath: '/wp-content/plugins/gravityforms-cui-customizations/assets/dist/',
  },
  module: {
    rules: [
      {
        test: /\.js$/,
        exclude: /node_modules/,
        use: {
          loader: 'babel-loader',
          options: {
            presets: ['@babel/preset-env'],
          },
        },
      },
      {
        test: /\.s?css$/i,
        use: [MiniCssExtractPlugin.loader, 'css-loader', 'sass-loader'],
      },
    ],
  },
  plugins: [
    new MiniCssExtractPlugin({
      filename: '[name].css',
    }),
  ],
  optimization: {
    splitChunks: {
      chunks: 'all',
    },
  },
};
