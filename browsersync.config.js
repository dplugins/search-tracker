module.exports = {
    proxy: "http://docs-theme.local/", // Change to your local WordPress URL
    files: [
      "build/**/*.css",
      "build/**/*.js",
      "src/**/*.js",
      "src/**/*.scss",
      "src/**/*.css",
      "*.php",
      "app/**/*.php"
    ],
    watchEvents: ["change", "add"],
    open: false
  };