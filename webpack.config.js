const path = require('path');

module.exports = {
    entry: {
        'app': './frontend/app.js',
    },
    output: {
        filename: '[name].min.js',
        path: path.resolve(__dirname, 'www/js')
    },
    module: {
        rules: [
            {
                test: /bootstrap\.native/,
                use: {
                    loader: 'bootstrap.native-loader'
                }
            },
            {
                test: /\.js$/,
                exclude: /node_modules/,
                use: {
                    loader: 'babel-loader',
                    options: {
                        presets: ['@babel/preset-env']
                    }
                }
            },
        ]
    }
};
