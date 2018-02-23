var ExtractTextPlugin = require('extract-text-webpack-plugin');
const config = {
    entry: "./src/js/index.js",
    output: {
        filename: "bundle.js",
        path: __dirname + "/plugins/webpack"
    },
    resolve: {
        alias: {
            jquery: "jquery/src/jquery"
        }
    },
    module: {
        loaders: [
            {
                test: /\.js/,
                loader: 'babel-loader',
                include: __dirname + '/src/js',
            }, {
                test: /\.css/,
                loader: ExtractTextPlugin.extract("css")
            }
            // {
            //     test: /\.css/,
            //     loaders: ['style', 'css'],
            //     include: __dirname + '/src/js'
            // }
        ],
        rules: [{
            test: require.resolve('jquery'),
            use: [{
                loader: 'expose-loader',
                options: 'jQuery'
            }, {
                loader: 'expose-loader',
                options: '$'
            }]
        }]
    },
    plugins: [
        new ExtractTextPlugin("styles.css")
    ]
};

module.exports = config;