const {join} = require('path');

/**
 * @type {import("puppeteer").Configuration}
 */
module.exports = {
  // Pindahkan letak download Chromium ke dalam folder wa-bot/.cache/puppeteer
  // Ini mencegah error "Executable doesn't exist" karena perbedaan user root vs user biasa di aaPanel/Linux
  cacheDirectory: join(__dirname, '.cache', 'puppeteer'),
};
