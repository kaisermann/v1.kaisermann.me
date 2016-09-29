'use strict';
module.exports = function (grunt)
{
	grunt.initConfig(
	{
		stylus:
		{
			build:
			{
				options:
				{
					lineno: true,
					compress: false
				},
				files: [
				{
					expand: true,
					cwd: 'assets/css/styl',
					src: ['*.styl'],
					dest: 'assets/css/',
					ext: '.css'
				}]
			}
		},
		clean:
		{
			css:
			{
				src: ['assets/css/*.css', '!assets/css/main.min.css']
			},
		},
		autoprefixer:
		{
			build:
			{
				expand: true,
				cwd: 'assets/css/',
				src: ['**/*.css','!vendor/**.css','!main.min.css'],
				dest: 'assets/css/'
			},
			all:
			{
				expand: true,
				cwd: 'assets/css/',
				src: ['**/*.css'],
				dest: 'assets/css/'
			}
		},
		cssmin:
		{
			combine:
			{
				files:
				{
					'assets/css/main.min.css': ['assets/css/vendor/*.css', 'assets/css/*.css', 'assets/css/style.css', '!assets/css/main.min.css', '!assets/css/header.css']
				}
			}
		},
		uglify:
		{
			my_target:
			{
				files:
				{
					'assets/js/main.min.js': ['assets/js/vendor/jquery.js','assets/js/vendor/*.js', 'assets/js/*.js', 'assets/js/core.js', '!assets/js/main.min.js']
				}
			}
		},
		watch:
		{
			options:
			{
				livereload: true
			},
			styl:
			{
				files: ['assets/css/**/*.*','!assets/css/*.css'],
				tasks: ['css']
			},
			js:
			{
				files: ['assets/js/*.js', '!assets/js/main.min.js'],
				tasks: ['uglify']
			},
			php:
			{
				files: ['**/*.php']
			}
		}
	});
	// Load tasks
	grunt.loadNpmTasks('grunt-contrib-clean');
	grunt.loadNpmTasks('grunt-contrib-stylus');
	grunt.loadNpmTasks('grunt-contrib-cssmin');
	grunt.loadNpmTasks('grunt-contrib-watch');
	grunt.loadNpmTasks('grunt-contrib-uglify');
	grunt.loadNpmTasks('grunt-autoprefixer');

	// Register tasks
	grunt.registerTask('default', ['cssmin', 'uglify']);
	grunt.registerTask('dev', ['watch']);
	grunt.registerTask('css', ['stylus', 'autoprefixer:build', 'cssmin', /*'clean:css'*/]);
	grunt.registerTask('pref', ['autoprefixer:all', 'cssmin']);
};
