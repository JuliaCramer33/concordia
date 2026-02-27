const { series, watch } = require('gulp');
const browserSync = require('browser-sync').create();
// PHPCS disabled in watcher to avoid crashes when local standards are missing
const { devstyles } = require('./devstyles');
const { js } = require('./js');
const { lintstyles } = require('./lintstyles');
const { htmlcheck } = require('./htmlcheck');
const { lintjs } = require('./lintjs');

function serve() {
  const proxy = process.env.WP_DEV_URL || 'http://localhost:8000';
  browserSync.init({ proxy, open: false, notify: false, ghostMode: false });

  // Watch SCSS sources
  watch('assets/scss/**/*.scss', series(devstyles, lintstyles, reload));

  // Watch JS sources only (avoid watching outputs)
  watch('assets/js/src/**/*.js', series(js, lintjs, reload));
  // Do not run PHPCS in watch; run lint:php manually when desired
  watch(['**/*.php', '!node_modules/**', '!vendor/**'], series(htmlcheck, reload));
  watch(['**/*.php', '**/*.html', 'theme.json']).on('change', browserSync.reload);

  function reload(cb) { browserSync.reload(); cb(); }
}

exports.watch = series(devstyles, js, serve);


