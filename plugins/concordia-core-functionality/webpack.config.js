const entry = require( 'webpack-glob-entry' );
const path = require( 'path' );
const TerserPlugin = require( 'terser-webpack-plugin' );
const CopyPlugin = require( 'copy-webpack-plugin' );

module.exports = {
	entry: entry( './src/*.js' ),
	output: {
		path: path.resolve( __dirname, 'assets/js' ),
		filename: '[name].min.js',
		sourceMapFilename: '[file].map',
	},
	externals: {
		jquery: 'jQuery',
	},
	mode: 'development',
	devtool: 'source-map',
	module: {
		rules: [
			{
				test: /\.js$/,
				exclude: /(node_modules|bower_components)/,
				use: [ 'babel-loader', 'eslint-loader' ],
			},
		],
	},
	optimization: {
		minimize: true,
		minimizer: [ new TerserPlugin( {
			terserOptions: {
				output: {
					comments: false,
				},
			},
			extractComments: false,
		} ) ],
	},
};
