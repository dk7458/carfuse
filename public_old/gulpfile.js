const gulp = require("gulp");
const cleanCSS = require("gulp-clean-css");
const uglify = require("gulp-uglify");
const concat = require("gulp-concat");

gulp.task("styles", function () {
    return gulp.src("css/**/*.css")
        .pipe(concat("main.min.css"))
        .pipe(cleanCSS())
        .pipe(gulp.dest("dist/css"));
});

gulp.task("scripts", function () {
    return gulp.src("js/**/*.js")
        .pipe(concat("main.min.js"))
        .pipe(uglify())
        .pipe(gulp.dest("dist/js"));
});

gulp.task("default", gulp.parallel("styles", "scripts"));
