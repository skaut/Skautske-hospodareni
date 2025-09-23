import path from 'path';
import webpack from 'webpack';
import MiniCssExtractPlugin from 'mini-css-extract-plugin';

export default {
    entry: {
        'app': './frontend/app.ts',
    },
    output: {
        filename: 'js/[name].min.js',
        path: path.resolve(import.meta.dirname, 'www')
    },
    module: {
        rules: [
            {
                test: /\.tsx?$/,
                use: 'ts-loader',
                exclude: /node_modules/
            },
            {
                test: /\.js$/,
                exclude: /node_modules/,
                use: {
                    loader: 'babel-loader',
                    options: {
                        presets: ['@babel/preset-env'],
                    }
                }
            },
            {
                test: /\.s[ac]ss$/,
                use: [
                    MiniCssExtractPlugin.loader,
                    'css-loader',
                    'postcss-loader',
                    {
                        loader: 'sass-loader',
                        options: {
                            sassOptions: {
                                // umlčí warnings z node_modules (Bootstrap apod.)
                                quietDeps: true,
                                // volitelné – potlačí konkrétní typy hlášek (Sass >=1.77)
                                silenceDeprecations: ['mixed-decls', 'color-functions', 'import'],
                            },
                        },
                    },
                ],
            }
        ]
    },
    plugins: [
        new webpack.IgnorePlugin({resourceRegExp: /^\.\/locale$/,contextRegExp: /moment$/}),
        new MiniCssExtractPlugin({
            // Options similar to the same options in webpackOptions.output
            // both options are optional
            filename: 'css/[name].css',
        }),
        // Useful for bundle size analysis:
        // new (require('webpack-bundle-analyzer').BundleAnalyzerPlugin),
    ],
    resolve: {
        extensions: ['.ts', '.js', '.json', '.scss'],
    }
};
