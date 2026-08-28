import path from 'path';
import webpack from 'webpack';
import MiniCssExtractPlugin from 'mini-css-extract-plugin';

// The service worker has to sit in the site root to control the whole application
// and it compiles against the web worker library instead of the DOM one.
const serviceWorkerDir = path.resolve(import.meta.dirname, 'frontend/sw');

export default {
    entry: {
        'app': './frontend/app.ts',
        'sw': './frontend/sw/serviceWorker.ts',
    },
    output: {
        filename: (pathData) => pathData.chunk.name === 'sw' ? '[name].js' : 'js/[name].min.js',
        path: path.resolve(import.meta.dirname, 'www')
    },
    module: {
        rules: [
            {
                test: /\.tsx?$/,
                include: serviceWorkerDir,
                use: {
                    loader: 'ts-loader',
                    options: {
                        configFile: path.resolve(import.meta.dirname, 'tsconfig.sw.json'),
                    },
                },
            },
            {
                test: /\.tsx?$/,
                use: 'ts-loader',
                exclude: [/node_modules/, serviceWorkerDir]
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
                                silenceDeprecations: ['mixed-decls', 'color-functions'],
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
