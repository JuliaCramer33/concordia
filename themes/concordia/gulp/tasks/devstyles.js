const { src, dest } = require('gulp');
const sass = require('gulp-dart-sass');
const postcss = require('gulp-postcss');
const sourcemaps = require('gulp-sourcemaps');
const rename = require('gulp-rename');

const entries = ['assets/scss/front.scss', 'assets/scss/editor.scss'];

function devstyles() {
  return src(entries, { allowEmpty: true })
    .pipe(sourcemaps.init())
    .pipe(sass({ outputStyle: 'expanded', quietDeps: true }).on('error', sass.logError))
    .pipe(postcss([require('autoprefixer')()]))
    .pipe(rename((path) => {
      if (path.basename === 'front') path.basename = 'global';
      if (path.basename === 'editor') path.basename = 'gutenberg-editor-styles';
    }))
    .pipe(sourcemaps.write('.'))
    .pipe(dest('css/prod'));
}

exports.devstyles = devstyles;


