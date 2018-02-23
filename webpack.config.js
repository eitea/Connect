const config = {
    entry: "./src/js/index.js",
    output: {
        filename: "bundle.js",
        path: __dirname + "/plugins/webpack"
    },
    module: {
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
    }
};

module.exports = config;