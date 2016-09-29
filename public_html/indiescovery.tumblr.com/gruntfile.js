'use strict';
module.exports = function(grunt) {
    grunt.initConfig({
        stylus: {
            build: {
                options: {
                    linenos: true,
                    compress: false
                },
                files: [{
                        expand: true,
                        cwd: 'assets/css/styl',
                        src: ['**/style.styl'],
                        dest: 'assets/css/',
                        ext: '.css'
                    }]
            }
        },
        clean: {
            css: {
                src: ['assets/css/*.css', '!assets/css/main.min.css']
            },
        },
        autoprefixer: {
            options: {
                browsers: ['last 7 version', 'ie 8', 'ie 9']
            },
            build: {
                expand: true,
                cwd: 'assets/css',
                src: ['**/*.css', "!vendor/**/*.css"],
                dest: 'assets/css/'
            }
        },
        cssmin: {
            combine: {
                files: {
                    'assets/css/main.min.css': ['assets/css/vendor/*.css', 'assets/css/*.css', '!assets/css/main.min.css']
                }
            }
        },
        uglify: {
            my_target: {
                files: {
                    'assets/js/main.min.js': ['assets/js/vendor/*.js', 'assets/js/*.js', 'assets/js/core.js', '!assets/js/main.min.js'],
                    'assets/js/jquery/jquery.min.js': ['assets/js/jquery/*.js', '!assets/js/jquery/jquery.min.js']
                }
            }
        },
        watch: {
            options: {
                livereload: true
            },
            styl: {
                files: ['assets/css/styl/*.styl'],
                tasks: ['css']
            },
            js: {
                files: ['assets/js/*.js', '!assets/js/main.min.js'],
                tasks: ['uglify']
            },
            jquery: {
                files: ['assets/js/jquery/*.js', '!assets/js/jquery/jquery.min.js'],
                tasks: ['uglify']
            },
            php: {
                files: ['**/*.php']
            }
        },
        uncss: {
            dist: {
                files: {
                    'assets/css/tidy.css': ['**/*.php']
                }
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
    grunt.loadNpmTasks('grunt-uncss');

    // Register tasks
    grunt.registerTask('default', ['cssmin', 'uglify']);
    grunt.registerTask('dev', ['watch']);
    grunt.registerTask('css', ['stylus', 'autoprefixer', 'cssmin']);
    //grunt.registerTask('css', ['stylus', 'autoprefixer', 'cssmin', 'clean:css']);
};