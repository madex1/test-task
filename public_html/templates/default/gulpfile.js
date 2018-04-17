let gulp = require('gulp');
let cleanCSS = require('gulp-clean-css');
let concat = require('gulp-concat');
let uglify = require('gulp-uglify');
let pump = require('pump');

/* заменить пути на пути к файлам */
let css = [
  "css/bootstrap.css",
  "css/jpreloader.css",
  "css/animate.css",
  "css/plugin.css",
  "css/owl.carousel.css",
  "css/owl.theme.css",
  "css/owl.transitions.css",
  "css/magnific-popup.css",
  "css/style.css",
  "css/custom.css",
  "css/bg.css",
  "css/color.css",
  "fonts/font-awesome/css/font-awesome.css",
  "fonts/et-line-font/style.css"
];
let js = [
    "js/jquery.min.js",
    "js/jpreLoader.js",
    "js/bootstrap.min.js",
    "js/jquery.isotope.min.js",
    "js/easing.js",
    "js/jquery.flexslider-min.js",
    "js/jquery.scrollto.js",
    "js/owl.carousel.js",
    "js/jquery.countTo.js",
    "js/classie.js",
    "js/video.resize.js",
    "js/validation.js",
    "js/wow.min.js",
    "js/jquery.magnific-popup.min.js",
    "js/enquire.min.js",
    "js/designesia.js",
    "js/custom.js"
]

gulp.task('min-css', () => {
  return gulp.src(css)
    .pipe(cleanCSS())
    .pipe(concat('all.min.css'))
    .pipe(gulp.dest('css'));
});

gulp.task('compress', function (cb) {
  pump([
      gulp.src(js),
      uglify(),
      concat('all.min.js'),
      gulp.dest('js')
    ],
    cb
  );
});

gulp.task('min-css2', function (cb) {
  pump([
      gulp.src(js),
      cleanCSS(),
      concat('all.min.css'),
      gulp.dest('css')
    ],
    cb
  );
});

gulp.task('minall', function(){
    gulp.start('compress');
    gulp.start('min-css');
})
