/**
 * This template was politely stolen from this Gist source:
 * https://gist.github.com/joelpittet/11405038
 * And took some inspiration from this source too:
 * https://github.com/google/web-starter-kit/blob/master/gulpfile.js
 */

// Include gulp
var gulp = require('gulp');

// Include yargs
var argv = require('yargs').argv;

var oldName = argv['old-name'];
var newName = argv['new-name'];

// Include Our Plugins
// Plugins for processing
var autoprefixer = require('gulp-autoprefixer');
var concat = require('gulp-concat');
var imagemin = require('gulp-imagemin');
var jshint = require('gulp-jshint');
var modernizr = require('gulp-modernizr');
var pngcrush = require('imagemin-pngcrush');
var sass = require('gulp-ruby-sass');
var sourcemaps = require('gulp-sourcemaps');
var stylish = require('jshint-stylish');
var uglify = require('gulp-uglify');
var wiredep = require('wiredep').stream;

// Utility plugins
var gulpFilter = require('gulp-filter');
var livereload = require('gulp-livereload');
var mainBowerFiles = require('main-bower-files');
var notify = require('gulp-notify');
var shell = require('gulp-shell');

// Configs
var AUTOPREFIXER_BROWSERS = [
  'ie >= 9',
  'ie_mob >= 9',
  'ff >= 30',
  'chrome >= 34',
  'safari >= 7',
  'opera >= 23',
  'ios >= 7',
  'android >= 4.4',
  'bb >= 10'
];

var MODERNIZR_FILES = [
  'js/**/*.js',
  'css/**/*.css',
  '!js/bower.js',
  '!js/vendor.js',
  '!js/modernizr.js'
];

var JS_FILES = [
  'js/*.js',
  '!Gruntfile.js',
  '!gulpfile.js',
  '!js/bower.js',
  '!js/vendor.js',
  '!js/modernizr.js'
];

/**
 * Internal tasks for Gulp
 */

// Bower Task
gulp.task('bower', function() {
  gulp.src(mainBowerFiles())
    .pipe(gulpFilter(['**/*.css']))
    .pipe(concat('bower.css'))
    .pipe(gulp.dest('css/'));

  gulp.src('styles/**/*.scss')
    .pipe(wiredep())
    .pipe(gulp.dest('styles'));

  gulp.src(mainBowerFiles())
    .pipe(gulpFilter(['**/*.js']))
    .pipe(concat('bower.js'))
    .pipe(gulp.dest('js/'));

  return notify({ message: 'Bower task complete' });
});

// Lint Task
gulp.task('lint', function() {
  return gulp.src(JS_FILES)
    .pipe(jshint('.jshintrc'))
    .pipe(jshint.reporter('jshint-stylish'))
    .pipe(livereload());
});

// Modernizr Task
gulp.task('modernizr', function() {
  return gulp.src(MODERNIZR_FILES)
    .pipe(modernizr())
    .pipe(uglify())
    .pipe(gulp.dest('js/'))
    .pipe(livereload());
});

// Compress images
gulp.task('images', function () {
  return gulp.src('images/*')
    .pipe(imagemin({
      progressive: true,
      svgoPlugins: [{removeViewBox: false}],
      use: [pngcrush()]
    }))
    .pipe(gulp.dest('img/'))
		.pipe(livereload());
});

// Compile Our Sass
gulp.task('styles', function() {
  return sass('styles/', { sourcemap: true, style: 'expanded' })
    .on('error', function (err) {
      console.error('Error', err.message);
    })
    .pipe(autoprefixer({ browsers: AUTOPREFIXER_BROWSERS }))
    .pipe(sourcemaps.write())
    .pipe(gulp.dest('css/'))
    .pipe(notify({ message: 'Styles task complete' }))
		.pipe(livereload());
});

// Concatenate & Minify JS
gulp.task('scripts', function() {
  return gulp.src('')
    .pipe(notify({ message: 'Scripts placeholder for now' }))
    .pipe(livereload());
});

/**
 * Tasks for the end user
 */

// Run drush to clear the theme registry.
gulp.task('drush', shell.task([
  'drush cr'
]));

// Remove any .info files in the node_modules folder
gulp.task('remove-info', shell.task([
  'find node_modules -name "*.info" -delete'
]));

// Provide a gulp task to rename the theme and do a search and replace for the theme name in functions
gulp.task('rename', function() {
  if (typeof oldName != 'undefined' && typeof newName != 'undefined') {
    return gulp.src('').pipe(shell([
      'perl -pi -w -e \'s/' + oldName + '/' + newName + '/g;\' ' + oldName + '.info',
      'perl -pi -w -e \'s/' + oldName + '/' + newName + '/g;\' template.php',
      'perl -pi -w -e \'s/' + oldName + '/' + newName + '/g;\' includes/form.inc',
      'perl -pi -w -e \'s/' + oldName + '/' + newName + '/g;\' includes/menu.inc',
      'perl -pi -w -e \'s/' + oldName + '/' + newName + '/g;\' includes/theme.inc',
      'perl -pi -w -e \'s/' + oldName + '/' + newName + '/g;\' includes/views.inc',
      'mv ' + oldName + '.info ' + newName + '.info'
    ]));
  } else {
    return gulp.src('').pipe(notify({ message: 'Cannot rename without the old name and the new name. Run "gulp rename --old-name $OLD_NAME --new-name $NEW_NAME"' }))
  }
});

// Default Task
gulp.task('default', ['bower', 'styles', 'images', 'scripts']);

// Watch Files For Changes
gulp.task('watch', function() {
  livereload.listen();

  gulp.watch('js/**/*.js', ['lint', 'scripts']);
  gulp.watch('styles/**/*.scss', ['styles']);

  gulp.watch('**/*.{php,inc,info}').on('change', function(file) {
    gulp.src(file.path).pipe(livereload());
  });
});

// Build Task - point to the default task
gulp.task('build', ['default']);
