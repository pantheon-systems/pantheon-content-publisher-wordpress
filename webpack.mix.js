const mix = require('laravel-mix');
const NodePolyfillPlugin = require("node-polyfill-webpack-plugin");
const tailwindcss = require('tailwindcss');
const postCssPrefixSelector = require('postcss-prefix-selector');

const excludedSelectors = [
  '.pcc-content',
  '.pcc-notice',
  '.pcc-icon',
  '.pcc-post-title-container'
];

const shouldExcludeSelector = (selector) => excludedSelectors.includes(selector);

mix.options({
  manifest: false,
  legacyNodePolyfills: true
}).webpackConfig({
  plugins: [
    new NodePolyfillPlugin(),
  ],
  resolve: {
    fallback: {
      fs: false,
      child_process: false
    }
  }
}).js('assets/js/app.js', 'assets/dist').js('assets/js/pcc-front.js', 'assets/dist').postCss('assets/css/app.css', 'assets/dist', [
  tailwindcss('./tailwind.config.js'),
  postCssPrefixSelector({
    prefix: '.pcc-content', // Replace with your desired class
    transform(prefix, selector, prefixedSelector) {
      // Exclude 'html' and 'body' tags from being prefixed
      if (shouldExcludeSelector(selector)) {
        return selector;
      }
      return prefixedSelector;
    },
  }),
]);
