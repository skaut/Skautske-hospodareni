module.exports = {
    entry: './frontend/app.js',
    output: {
        filename: './www/js/bundle.js'
    },
    module: {
        rules: [
            {test: /\.js$/, exclude: /node_modules/, loader: "babel-loader"}
        ]
    }
};
