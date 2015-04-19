/**
 * Build Plugin
 *
 * @author Usability Dynamics, Inc.
 * @version 2.0.0
 * @param grunt
 */
module.exports = function build( grunt ) {

  // Automatically Load Tasks.
  require( 'load-grunt-tasks' )( grunt, {
    pattern: 'grunt-*',
    config: './package.json',
    scope: 'devDependencies'
  });

  grunt.initConfig( {

    package: grunt.file.readJSON( 'composer.json' ),
    
    markdown: {
      all: {
        files: [
          {
            expand: true,
            src: 'readme.md',
            dest: 'static/',
            ext: '.html'
          }
        ],
        options: {
          markdownOptions: {
            gfm: true,
            codeLines: {
              before: '<span>',
              after: '</span>'
            }
          }
        }
      }
    },

    // Compile LESS
    less: {
      production: {
        options: {
          yuicompress: true,
          relativeUrls: true
        },
        files: {}
      },
      development: {
        options: {
          relativeUrls: true
        },
        files: {}
      }
    },

    watch: {
      options: {
        interval: 100,
        debounceDelay: 500
      },
      less: {
        files: [
          'static/styles/src/*.*'
        ],
        tasks: [ 'less' ]
      },
      js: {
        files: [
          'static/scripts/src/*.*'
        ],
        tasks: [ 'uglify' ]
      }
    },

    uglify: {
      production: {
        options: {
          mangle: false,
          beautify: false
        },
        files: [
          {
            expand: true,
            cwd: 'static/scripts/src',
            src: [ '*.js' ],
            dest: 'static/scripts'
          }
        ]
      },
      staging: {
        options: {
          mangle: false,
          beautify: true
        },
        files: [
          {
            expand: true,
            cwd: 'static/scripts/src',
            src: [ '*.js' ],
            dest: 'static/scripts'
          }
        ]
      }
    },

    clean: {
      update: [
        "composer.lock"
      ],
      all: [
        "vendor",
        "composer.lock"
      ]
    },

    shell: {
      /**
       * Make Production Build and create new tag ( release ) on Github.
       */
      release: {
        command: function( tag ) {
          return [
            'sh build.sh ' + tag
          ].join( ' && ' );
        },
        options: {
          encoding: 'utf8',
          stderr: true,
          stdout: true
        }
      },
      /**
       * Runs PHPUnit test, creates code coverage and sends it to Scrutinizer
       */
      coverageScrutinizer: {
        command: [
          'grunt phpunit:circleci --coverage-clover=coverage.clover',
          'wget https://scrutinizer-ci.com/ocular.phar',
          'php ocular.phar code-coverage:upload --format=php-clover coverage.clover'
        ].join( ' && ' ),
        options: {
          encoding: 'utf8',
          stderr: true,
          stdout: true
        }
      },
      /**
       * Runs PHPUnit test, creates code coverage and sends it to Code Climate
       */
      coverageCodeClimate: {
        command: [
          'grunt phpunit:circleci --coverage-clover build/logs/clover.xml',
          'CODECLIMATE_REPO_TOKEN='+ process.env.CODECLIMATE_REPO_TOKEN + ' ./vendor/bin/test-reporter'
        ].join( ' && ' ),
        options: {
          encoding: 'utf8',
          stderr: true,
          stdout: true
        }
      },
      /**
       * Composer Install
       */
      install: {
        command: function( env ) {
          if( typeof env !== 'undefined' && env == 'dev' ) {
            return [
              "composer install"
            ].join( ' && ' );
          } else {
            return [
              "composer install --no-dev",
              "rm -rf ./vendor/composer/installers",
              "find ./vendor -name .git -exec rm -rf '{}' \\;",
              "find ./vendor -name .svn -exec rm -rf '{}' \\;",
            ].join( ' && ' );
          }
        },
        options: {
          encoding: 'utf8',
          stderr: true,
          stdout: true
        }
      }
    },
    
    // Runs PHPUnit Tests
    phpunit: {
      classes: {},
      options: {
        bin: './vendor/bin/phpunit',
      },
      local: {
        configuration: './test/php/phpunit.xml'
      },
      circleci: {
        configuration: './test/php/phpunit-circle.xml'
      }
    }

  });

  // Register tasks
  grunt.registerTask( 'default', [ 'markdown', 'less' , 'uglify' ] );
  
  // Run default Tests
  grunt.registerTask( 'localtest', [ 'phpunit:local' ] );
  grunt.registerTask( 'test', [ 'phpunit:circleci' ] );
  
  // Run coverage tests
  grunt.registerTask( 'testscrutinizer', [ 'shell:coverageScrutinizer' ] );
  grunt.registerTask( 'testcodeclimate', [ 'shell:coverageCodeClimate' ] );
  
  // Install Environment
  grunt.registerTask( 'install', 'Run all my install tasks.', function( env ) {
    if ( env == null ) env = 'no-dev';
    grunt.task.run( 'clean:all' );
    grunt.task.run( 'shell:install:' + env );
  });
  
  // Make Production Build and create new tag ( release ) on Github.
  grunt.registerTask( 'release', 'Run all my release tasks.', function( tag ) {
    if ( tag == null ) grunt.warn( 'Release tag must be specified, like release:1.0.0' );
    grunt.task.run( 'shell:release:' + tag );
  });

};
