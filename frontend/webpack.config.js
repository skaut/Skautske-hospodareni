module.exports = {
    entry: './app.tsx',
    output: {
        filename: '../www/js/bundle.js'
    },
    resolve: {
        extensions: [".ts", ".tsx", ".js", ".jsx"]
    },
    module: {
        rules: [
            {test: /\.tsx?$/, loaders: ["babel-loader", "awesome-typescript-loader"]},
            {test: /\.js$/, exclude: /node_modules/, loader: "babel-loader"}
        ]
    }
};
gi
