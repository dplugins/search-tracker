module.exports = {
    proxy: "http://docs-theme.local/", // Change to your local WordPress URL
    files: [
      "*.css",
      "assets/css/*.css",
      "*.php",
      "app/**/*.php",
      "*.js",
      "assets/js/*.js"
    ],
    watchEvents: ["change", "add"],
    open: false
  };