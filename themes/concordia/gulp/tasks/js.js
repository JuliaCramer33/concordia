const { src, dest } = require('gulp');
const named = require('vinyl-named');
const webpackStream = require('webpack-stream');
const TerserPlugin = require('terser-webpack-plugin');

function js() {
  const isProd = process.env.NODE_ENV === 'production';
  return src(['assets/js/src/front-end.js', 'assets/js/src/gutenberg-editor.js'], { allowEmpty: true })
    .pipe(named())
    .pipe(
      webpackStream({
        mode: isProd ? 'production' : 'development',
        devtool: isProd ? false : 'inline-source-map',
        module: {
          rules: [
            {
              test: /\.m?js$/,
              exclude: /(node_modules)/,
              use: {
                loader: 'babel-loader',
                options: {
                  presets: [
                    [
                      '@babel/preset-env',
                      { targets: 'defaults', useBuiltIns: false }
                    ]
                  ]
                }
              }
            }
          ]
        },
        optimization: {
          minimize: isProd,
          minimizer: [new TerserPlugin({ extractComments: false })]
        },
        output: { filename: '[name].js' }
      })
    )
    .pipe(dest('js/prod'));
}

exports.js = js;


