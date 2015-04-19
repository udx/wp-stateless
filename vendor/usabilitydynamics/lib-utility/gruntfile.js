/**
 * Library Build.
 *
 * @author potanin@UD
 * @version 1.2.0
 * @param grunt
 */
module.exports = function buildLibrary( grunt ) {

  // Require Utility Modules.
  var joinPath  = require( 'path' ).join;
  var findup    = require( 'findup-sync' );

  // Determine Paths.
  var _paths = {
    composer: findup( 'composer.json' ),
    vendor: findup( 'vendor' ),
    phpTests: findup( 'test/php' ),
    jsTests: findup( 'test/js' ),
    autoload: findup( 'vendor/autoload.php' ) || findup( '**/autoload.php' )
  };

  grunt.initConfig({

    // Read Composer File.
    package: grunt.file.readJSON( _paths.composer ),

    // PHP Unit Tests.
    phpunit: {
      classes: {
        dir: joinPath( _paths.phpTests, '*.php' )
      },
      options: {
        bin: 'phpunit',
        bootstrap: _paths.autoload,
        colors: true
      }
    },

    // Generate Documentation.
    yuidoc: {
      compile: {
        name: '<%= package.name %>',
        description: '<%= package.description %>',
        version: '<%= package.version %>',
        url: '<%= package.homepage %>',
        options: {
          paths: [ 'lib', 'scripts' ],
          outdir: 'static/codex/'
        }
      }
    },

    // Compile LESS.
    less: {
      development: {
        options: {
          relativeUrls: true
        },
        files: {
          //'styles/requires.dev.css': [ 'styles/src/requires.less' ]
        }
      },
      production: {
        options: {
          yuicompress: true,
          relativeUrls: true
        },
        files: {
          //'styles/requires.css': [ 'styles/src/requires.less' ]
        }
      }
    },

    // Development Watch.
    watch: {
      options: {
        interval: 100,
        debounceDelay: 500
      },
      php: {
        files: [ 'lib/class-*.php' ],
        tasks: [ 'phpunit' ]
      },
      less: {
        files: [ 'styles/src/*.*' ],
        tasks: [ 'less' ]
      },
      js: {
        files: [ 'scripts/src/*.*' ],
        tasks: [ 'uglify' ]
      }
    },

    // Uglify Scripts.
    uglify: {
      development: {
        options: {
          preserveComments: true,
          beautify: true,
          wrap: false
        },
        files: [
          {
            expand: true,
            cwd: 'scripts/src',
            src: [ '*.js' ],
            dest: 'scripts'
          }
        ]
      },
      production: {
        options: {
          preserveComments: false,
          wrap: false
        },
        files: [
          {
            expand: true,
            cwd: 'scripts/src',
            src: [ '*.js' ],
            dest: 'scripts'
          }
        ]
      }
    },

    // Generate Markdown.
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

    // Clean for Development.
    clean: {
      all: [
        "vendor",
        "static/readme.md",
        "composer.lock",
        "styles/*.css",
        "scripts/*.js"
      ],
      update: [
        "composer.lock"
      ]
    },

    // CLI Commands.
    shell: {
      update: {
        options: {
          stdout: true
        },
        command: 'composer update --prefer-source'
      }
    },

    // Coverage Tests.
    mochacov: {
      options: {
        reporter: 'list',
        requires: [ 'should' ]
      },
      all: [ joinPath( _paths.jsTests, '*.js' ) ]
    },

    // Usage Tests.
    mochacli: {
      options: {
        requires: [ 'should' ],
        reporter: 'list',
        ui: 'exports',
        bail: false
      },
      all: [ joinPath( _paths.jsTests, '*.js' ) ]
    }

  });

  // Load NPM Tasks.
  grunt.loadNpmTasks( 'grunt-markdown' );
  grunt.loadNpmTasks( 'grunt-requirejs' );
  grunt.loadNpmTasks( 'grunt-contrib-yuidoc' );
  grunt.loadNpmTasks( 'grunt-contrib-uglify' );
  grunt.loadNpmTasks( 'grunt-contrib-watch' );
  grunt.loadNpmTasks( 'grunt-contrib-less' );
  grunt.loadNpmTasks( 'grunt-contrib-concat' );
  grunt.loadNpmTasks( 'grunt-contrib-clean' );
  grunt.loadNpmTasks( 'grunt-shell' );
  grunt.loadNpmTasks( 'grunt-mocha-cli' );
  grunt.loadNpmTasks( 'grunt-mocha-cov' );
  grunt.loadNpmTasks( 'grunt-phpunit' );

  // Register NPM Tasks.
  grunt.registerTask( 'default',        [ 'build' ] );

  // Installation.
  grunt.registerTask( 'install',        [ 'build' ] );

  // Build Task.
  grunt.registerTask( 'build',          [ 'markdown', 'less' , 'yuidoc', 'uglify' ] );

  // Run Unit Tests.
  grunt.registerTask( 'test',           [ 'phpunit', 'mochacli:all', 'mochacov:all' ] );

  // Build Distribution.
  grunt.registerTask( 'distribution',   [ 'mochacli:all', 'mochacov:all', 'clean:all', 'markdown', 'less:production', 'uglify:production' ] );

  // Update Environment.
  grunt.registerTask( 'update',         [ 'clean:update', 'shell:update' ] );

};