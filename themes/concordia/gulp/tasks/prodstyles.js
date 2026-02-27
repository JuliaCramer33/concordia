const { src, dest } = require('gulp');
const sass = require('gulp-dart-sass');
const postcss = require('gulp-postcss');
const rename = require('gulp-rename');

const entries = ['assets/scss/front.scss', 'assets/scss/editor.scss'];

function prodstyles() {
  return src(entries, { allowEmpty: true })
    .pipe(sass({ outputStyle: 'expanded', quietDeps: true }).on('error', sass.logError))
    .pipe(postcss([require('autoprefixer')(), require('cssnano')({ preset: 'default' })]))
    .pipe(rename((path) => {
      if (path.basename === 'front') path.basename = 'global';
      if (path.basename === 'editor') path.basename = 'gutenberg-editor-styles';
    }))
    .pipe(dest('css/prod'));
}

exports.prodstyles = prodstyles;


