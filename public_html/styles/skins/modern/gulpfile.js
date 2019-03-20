/** Created by bender on 19.11.15. */

var gulp = require('gulp'),
    gexec = require('gulp-exec'),
    csso = require('gulp-csso'),
    rename = require('gulp-rename'),
    postcss = require('gulp-postcss'),
    sourcemaps = require('gulp-sourcemaps'),
    autoprefixer = require('autoprefixer');

//Сборка тейблконтирола в теме Modern
gulp.task('smc',function (){
    var options = {
        continueOnError: true,
        pipeStdout: true
    };
    return gulp.src('').pipe(gexec('/usr/bin/php '+__dirname+'/design/js/smc/compile.php',options));
});

//Сборка скриптов общих для тем
gulp.task('common',function (){
    var options = {
        continueOnError: true,
        pipeStdout: true
    };
    return gulp.src('').pipe(gexec('/usr/bin/php '+__dirname+'/design/js/common/compile.php',options));
});