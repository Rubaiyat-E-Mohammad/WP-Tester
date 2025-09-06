const path = require('path');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');

module.exports = (env, argv) => {
  const isProduction = argv.mode === 'production';
  
  return {
    entry: {
      'modern-admin': './src/js/modern-admin.jsx',
      'dashboard': './src/js/components/Dashboard.jsx',
      'modern-styles': './src/scss/modern-admin.scss'
    },
    output: {
      path: path.resolve(__dirname, 'assets/dist'),
      filename: '[name].js',
      clean: true
    },
    module: {
      rules: [
        {
          test: /\.(js|jsx)$/,
          exclude: /node_modules/,
          use: {
            loader: 'babel-loader',
            options: {
              presets: ['@babel/preset-env', '@babel/preset-react']
            }
          }
        },
        {
          test: /\.(scss|css)$/,
          use: [
            MiniCssExtractPlugin.loader,
            'css-loader',
            'postcss-loader',
            'sass-loader'
          ]
        }
      ]
    },
    plugins: [
      new MiniCssExtractPlugin({
        filename: '[name].css'
      })
    ],
    resolve: {
      extensions: ['.js', '.jsx', '.scss', '.css']
    },
    externals: {
      'react': 'React',
      'react-dom': 'ReactDOM',
      'jquery': 'jQuery'
    },
    devtool: isProduction ? false : 'source-map'
  };
};
