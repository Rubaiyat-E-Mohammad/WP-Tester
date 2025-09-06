module.exports = {
  plugins: [
    require('postcss-preset-env')({
      stage: 1,
      features: {
        'custom-properties': false
      }
    })
  ]
};
