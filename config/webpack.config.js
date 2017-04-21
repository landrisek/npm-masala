var webpack = require('webpack');
var path = require('path');

var BUILD_DIR = path.resolve(__dirname, 'assets/components');
var APP_DIR = path.resolve(__dirname, 'app/components');

var config = {
    entry: { SurveyForm: APP_DIR + '/SurveyForm.jsx' },
    output: {
        path: BUILD_DIR,
        filename: '[name].js'
  },
  module : {
    loaders : [
      {
        test : /\.jsx?/,
        include : APP_DIR,
        loader : 'babel'
      }
    ]
  }
}


module.exports = config;