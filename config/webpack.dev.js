/** ../../../../node_modules/.bin/webpack -d --config webpack.dev.js  --watch-poll --watch  */
const merge = require('webpack-merge');
const common = require('./webpack.common.js');
var path = require('path');

var BUILD_DIR = path.resolve(__dirname, '../../../../assets/components/masala/js')

module.exports = merge(common, {
    output: {
        chunkFilename: '[name].js',
        filename: '[name].js',
        path: BUILD_DIR
    }}
)