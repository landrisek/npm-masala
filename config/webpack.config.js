var webpack = require('webpack');
var path = require('path');
var ManifestPlugin = require("webpack-manifest-plugin");
var WebpackMd5HashPlugin = require('webpack-md5-hash');
var __DEV__ = JSON.parse(process.env.BUILD_DEV || "true");
/* var __DEV__ = false /**/
var ASSETS_DIR = 'assets/masala/js';
var BUILD_DIR = path.resolve(__dirname, ASSETS_DIR);
var APP_DIR = path.resolve(__dirname, 'app/Masala/react');

module.exports = {
  entry: { ContentForm: APP_DIR + '/ContentForm.jsx',
            EditForm: APP_DIR + '/EditForm.jsx',
            Grid: APP_DIR + '/Grid.jsx',
            ImportForm: APP_DIR + '/ImportForm.jsx',
            ProcessForm: APP_DIR + '/ProcessForm.jsx'
  },
  devServer: {
        proxy: {
            '/app': {
                target: 'http://10.10.0.100/4camping.cz/lubo/sklad',
                secure: false
            }
        }
  },
    output: {
        path: BUILD_DIR,
        filename: __DEV__ ? '[name].js' : '[name].[chunkhash].js',
        chunkFilename: __DEV__ ? '[name].js' : '[name].[chunkhash].js'
  },
  module : {
    loaders : [
      {
        test : /\.jsx?/,
        exclude:/(node_modules|bower)/,
        include : APP_DIR,
        loader : 'babel-loader',
        query  :{
                presets:['react','es2015']
        }
      }
    ]
  },
  plugins: [
    new WebpackMd5HashPlugin(),
    new ManifestPlugin({fileName: 'manifest.json', basePath: '', publicPath: ASSETS_DIR + '/', stripSrc: /\.js/})
  ],
  bail: true
}