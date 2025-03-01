module.exports = {
    proxy: "http://docs-theme.local/", // Change to your local WordPress URL
    files: [
      "*.css",
      "*.php",
      "*.js"
    ],
    watchEvents: ["change", "add"],
    open: false
  };