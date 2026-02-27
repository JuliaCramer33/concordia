const { src } = require('gulp');
const phpcs = require('gulp-phpcs');

function phpcsTask() {
  return src(['**/*.php', '!node_modules/**', '!vendor/**'])
    .pipe(phpcs({ bin: 'vendor/bin/phpcs', standard: '../../.phpcs.xml.dist', warningSeverity: 0 }))
    .pipe(phpcs.reporter('log'));
}

exports.phpcs = phpcsTask;


