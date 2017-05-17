const gulp 	= require('gulp');
const $ 	= require('gulp-load-plugins')();
const config = require('./package.json');

const WP_REPO = 'http://plugins.svn.wordpress.org/stream-manager/';
const ENTRY_FILE = 'stream-manager.php';
const BUILD_FILES = [
	'./assets/**/*',
	'./includes/**/*',
	'./README.txt',
	'./README.md',
	'./stream-manager.php'
];

// ----------------------------------------
//
//   Git: Checkout the master branch from git 
//   and pull down the latest.
//   
// ----------------------------------------

gulp.task('git:checkout', function(done) {
	return $.git.checkout('master', function (err) {
		if (err) throw err;
		done();
	});
}); 

gulp.task('git:pull', function(done) {
	return $.git.pull('origin', 'master', function (err) {
	    if (err) throw err;
	    done();
	});
});

gulp.task('git', $.sequence('git:checkout', 'git:pull'));

// ----------------------------------------
//
//   Version: Update the version number and 
//   commit and push the update
//   
// ----------------------------------------

gulp.task('version:plugin', function() {
	return gulp.src([ENTRY_FILE], { base: './' })
		.pipe($.replace(/(Version:\s+)([\d|.]+)/, '$1' + config.version))
		.pipe(gulp.dest('.'));
});

gulp.task('version:readme', function() {
	return gulp.src(['README.txt'], { base: './' })
		.pipe($.replace(/(Stable tag:\s+)([\d|.]+)/, '$1' + config.version))
		.pipe(gulp.dest('.'));
});

gulp.task('version:commit', function() {
	return gulp.src([ENTRY_FILE, 'README.txt'])
    	.pipe($.git.commit('Updated version #'));
});

gulp.task('version:push', function() {
	return $.git.push('origin', 'master', function (err) {
	    if (err) throw err;
	 });
})

gulp.task('version', $.sequence(
	'version:plugin', 
	'version:readme', 
	'version:commit',
	'version:push'
));

// ----------------------------------------
//
//   Svn: Checkout the SVN repository from wp.org.
//   Clean out what's already in the tag
//   and trunk folders, copy our files into 
//   the svn repo, and commit the updates.
//   
// ----------------------------------------
	
gulp.task('svn:checkout', function(done) {
	return $.svn.checkout(WP_REPO, 'svn', function(err){
        if(err) throw err;
        done();
    });
});

gulp.task('svn:delete', function() {
	return gulp.src(['./svn/tags/' + config.version + '/*', './svn/trunk/*'], {read:false})
		.pipe($.clean());
});

gulp.task('svn:copy', function() {
	return gulp.src(BUILD_FILES, { base: './' })
		.pipe(gulp.dest('./svn/tags/' + config.version))
		.pipe(gulp.dest('./svn/trunk'));
});

gulp.task('svn:add', function(done){
    return $.svn.add('svn/*', {args: '--force'}, function(err){
        if(err) throw err;
        done();
    });
});

gulp.task('svn:commit', function(done){
    return $.svn.commit('Releasing tag ' + config.version, {cwd: './svn'}, function(err){
        if(err) throw err;
        done();
    });
});

gulp.task('svn:cleanup', function() {
	return gulp.src('./svn', {read:false})
		.pipe($.clean());
});

gulp.task('svn', $.sequence(
	'svn:checkout', 
	'svn:delete', 
	'svn:copy', 
	'svn:add',
	'svn:commit', 
	'svn:cleanup'
));

// -------------------------------------
//
// Everything! Release the plugin to the wp plugin repo.
//   
// -------------------------------------

gulp.task('release', $.sequence('git', 'version', 'svn'));
