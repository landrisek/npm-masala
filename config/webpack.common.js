var webpack = require('webpack');
var path = require('path');
var ManifestPlugin = require("webpack-manifest-plugin");
var WebpackMd5HashPlugin = require('webpack-md5-hash');
var APP_DIR = path.resolve(__dirname, '../react');
var PUBLIC_DIR = 'assets/components/masala/js';

module.exports = {
    entry: { Grid: APP_DIR + '/Grid.jsx',
        ImportForm: APP_DIR + '/ImportForm.jsx',
        RowForm: APP_DIR + '/RowForm.jsx'
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