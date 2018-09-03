/** ./../../../node_modules/.bin/webpack -d --config webpack.dev.js --watch-poll --watch **/
const merge = require('webpack-merge');
const common = require('./webpack.config.js');
module.exports = merge(common, { devtool: 'inline-source-map', cache: false });