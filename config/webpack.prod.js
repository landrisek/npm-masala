const webpack = require('webpack');
const merge = require('webpack-merge');
const common = require('./webpack.common.js');
var path = require('path');

var BUILD_DIR = path.resolve(__dirname, '../../../assets/masala/js')

module.exports = merge(common, {
	output: {
        chunkFilename: '[name].[chunkhash].js',
        filename: '[name].[chunkhash].js',
        path: BUILD_DIR
    }});