const gulp         = require('gulp');
const autoprefixer = require('gulp-autoprefixer');
const babel        = require('gulp-babel');
const cleanCSS     = require('gulp-clean-css');
const concat       = require('gulp-concat');
const rename       = require('gulp-rename');
const sass         = require('gulp-sass');
const sourcemaps   = require('gulp-sourcemaps');
const uglify       = require('gulp-uglify');
const pump         = require('pump');

let sassFiles = 'assets/src/scss/main.scss';
let sassPartials = 'assets/src/scss/partials/**/*.scss';

let assetsCss = [
    'node_modules/bootstrap/dist/css/bootstrap.min.css',
    'node_modules/font-awesome/css/font-awesome.min.css',
    'node_modules/bootstrap-datepicker/dist/css/bootstrap-datepicker3.min.css',
    'node_modules/bootstrap-toggle/css/bootstrap-toggle.min.css',
    'node_modules/chosen-js/chosen.min.css',
    'node_modules/footable/css/footable.core.min.css',
    'node_modules/@yaireo/tagify/dist/tagify.css',
    'vendor/moxiecode/plupload/js/jquery.plupload.queue/css/jquery.plupload.queue.css'
];

let assetsJs = [
    'node_modules/bootstrap/dist/js/bootstrap.min.js',
    'node_modules/bootstrap-datepicker/dist/js/bootstrap-datepicker.min.js',
    'node_modules/bootstrap-toggle/js/bootstrap-toggle.min.js',
    'node_modules/chosen-js/chosen.jquery.min.js',
    'node_modules/footable/dist/footable.min.js',
    'node_modules/node-jen/jen.js',
    'node_modules/@yaireo/tagify/dist/jQuery.tagify.min.js',
    'node_modules/js-cookie/src/js.cookie.js',
    'node_modules/sprintf-js/dist/sprintf.min.js',
    'assets/lib/ckeditor/ckeditor.js',
    'assets/lib/flot/jquery.flot.min.js',
    'assets/lib/flot/jquery.flot.resize.min.js',
    'assets/lib/flot/jquery.flot.time.min.js',
    'vendor/moxiecode/plupload/js/plupload.full.min.js',
    'vendor/moxiecode/plupload/js/jquery.plupload.queue/jquery.plupload.queue.min.js'
];

let appJs = [
    'assets/src/js/jquery.psendmodal.js',
    'assets/src/js/jquery.validations.js',
    'assets/src/js/bulk.actions.js',
    'assets/src/js/dashboard.widgets.js',
    'assets/src/js/jquery.functions.js',
    'assets/src/js/main.js'
];

let dest = 'assets/';

gulp.task('sass', function () {
    gulp.src(sassFiles)
        .pipe(sourcemaps.init())
        .pipe(autoprefixer())
        .pipe(sass().on('error', sass.logError))
        .pipe(concat('main.css'))
        .pipe(sourcemaps.write('.'))
        .pipe(gulp.dest(dest + 'css/'));
    gulp.src(assetsCss)
        .pipe(sourcemaps.init())
        .pipe(autoprefixer())
        .pipe(sass().on('error', sass.logError))
        .pipe(concat('assets.css'))
        .pipe(sourcemaps.write('.'))
        .pipe(gulp.dest(dest + 'css/'));
});

gulp.task('javascript', function () {
    gulp.src(appJs)
        .pipe(sourcemaps.init())
        .pipe(concat('app.js'))
        .pipe(sourcemaps.write('.'))
        .pipe(gulp.dest(dest + 'js/'));
    gulp.src(assetsJs)
        .pipe(sourcemaps.init())
        .pipe(concat('assets.js'))
        .pipe(sourcemaps.write('.'))
        .pipe(gulp.dest(dest + 'js/'));
});

gulp.task('copy', function () {
    gulp.src('node_modules/bootstrap/fonts/*.*')
        .pipe(gulp.dest(dest + 'fonts/'));
    gulp.src('node_modules/font-awesome/fonts/*.*')
        .pipe(gulp.dest(dest + 'fonts/'));
    gulp.src('node_modules/jquery/dist/jquery.min.js')
        .pipe(gulp.dest(dest + 'lib/jquery/'));
    gulp.src('node_modules/jquery-migrate/dist/jquery-migrate.min.js')
        .pipe(gulp.dest(dest + 'lib/jquery-migrate/'));
});

gulp.task('minify-css', function () {
    return gulp.src(dest + 'css/*.css')
        .pipe(cleanCSS())
        .pipe(rename({ suffix: '.min' }))
        .pipe(gulp.dest(dest + 'css/'));
});

gulp.task('minify-js', function (cb) {
    pump([
        gulp.src(dest + 'js/*.js'),
        uglify(),
        rename({ suffix: '.min' }),
        gulp.dest(dest + 'js/')
    ], cb);
});

gulp.task('minify', ['minify-css', 'minify-js']);

gulp.task('build', ['copy', 'sass', 'javascript']);

gulp.task('prod', ['build', 'minify']);

gulp.task('watch', function () {
    gulp.watch(sassFiles, ['copy']);
    gulp.watch([sassFiles, sassPartials], ['sass']);
    gulp.watch([appJs], ['javascript']);
});

gulp.task('default', ['build', 'watch']);
