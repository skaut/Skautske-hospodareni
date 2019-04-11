const path = require('path');
const webpack = require('webpack');

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
    },
    plugins: [
        new webpack.IgnorePlugin(/^\.\/locale$/, /moment$/),
        // Useful for bundle size analysis:
        // new (require('webpack-bundle-analyzer').BundleAnalyzerPlugin),
    ]
};
