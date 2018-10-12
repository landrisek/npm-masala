/** ../../../node_modules/.bin/webpack -d --config webpack.dev.js */
var webpack = require('webpack');
var path = require('path');
var ManifestPlugin = require("webpack-manifest-plugin");
var WebpackMd5HashPlugin = require('webpack-md5-hash');
var APP_DIR = path.resolve(__dirname, '../react');
var PUBLIC_DIR = '/assets/masala/js';

module.exports = {
    entry: {  ContentForm: APP_DIR + '/ContentForm.jsx',
        Grid: APP_DIR + '/Grid.jsx',
        ImportForm: APP_DIR + '/ImportForm.jsx',
        RowForm: APP_DIR + '/RowForm.jsx'
    },
    devServer: {
        proxy: {
            '/app': {
                target: 'http://10.10.0.100/',
                secure: false
            }
        }
    },
    module : {
        loaders : [
            {
                test : /\.jsx?/,
                exclude:/(node_modules|bower)/,
                include : APP_DIR,
                loader : 'babel-loader',
                query  :{
                    presets:['react','env']
                }
            }
        ]
    },
    plugins: [
        new WebpackMd5HashPlugin(),
        new ManifestPlugin({fileName: 'manifest.json', basePath: '', publicPath: PUBLIC_DIR + '/', stripSrc: /\.js/})
    ],
    bail: true
}