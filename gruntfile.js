'use strict';
module.exports = function ( grunt ) {

	// load all grunt tasks matching the `grunt-*` pattern
	// Ref. https://npmjs.org/package/load-grunt-tasks
	require( 'load-grunt-tasks' )( grunt );

	grunt.initConfig( {
		// SCSS and Compass
		// Ref. https://npmjs.org/package/grunt-contrib-compass
		compass: {
			frontend: {
				options: {
					config: 'config.rb',
					force: true
				}
			},
			// Admin Panel CSS
			backend: {
				options: {
					sassDir: 'admin/css/sass/',
					cssDir: 'admin/css/'
				}
			}
		},
		// Uglify
		// Compress and Minify JS files
		// Ref. https://npmjs.org/package/grunt-contrib-uglify
		uglify: {
			options: {
				banner: '/*! \n * rtBiz Helpdesk JavaScript Library \n * @package rtBiz Helpdesk \n */'
			},
			frontend: {
				src: [
					'public/js/common.js',
					'public/js/app.js',
					'public/js/vendors/stickyfloat.js',
				    'public/js/vendors/plupupload/plupload.full.min.js',
				    'public/js/vendors/markdown/showdown.js',
                    'public/js/showdown-gui.js',
                    'public/js/vendors/markdown/showdown-table.js',
                    'public/js/vendors/markdown/showdown-github.js',
                    'public/js/vendors/markdown/showdown-prettify.js'
				],
				dest: 'public/js/helpdesk-min.js'
			},
			backend: {
				src: [
					'admin/js/vendors/moment.js',
					'public/js/vendors/plupupload/plupload.full.min.js',
					'admin/js/vendors/jquery.steps.js',
					'admin/js/setup-wizard.js',
					'public/js/common.js',
					'admin/js/admin.js',
				    'admin/js/rthd_plugin_check.js',
                    'public/js/vendors/markdown/showdown.js',
                    'public/js/showdown-gui.js',
                    'public/js/vendors/markdown/showdown-table.js',
                    'public/js/vendors/markdown/showdown-github.js',
                    'public/js/vendors/markdown/showdown-prettify.js'
				],
				dest: 'admin/js/helpdesk-admin-min.js'
			},
			support: {
				src: [
					'public/js/rt_support_form.js',
					'public/js/vendors/plupupload/plupload.full.min.js',
                    'public/js/vendors/markdown/showdown.js',
                    'public/js/showdown-gui.js',
                    'public/js/vendors/markdown/showdown-table.js',
                    'public/js/vendors/markdown/showdown-github.js',
                    'public/js/vendors/markdown/showdown-prettify.js'
				],
				dest: 'public/js/helpdesk-support-min.js'
			},
			shortcode: {
				src: [
					'public/js/helpdesk-shortcode.js'
				],
				dest: 'public/js/helpdesk-shortcode-min.js'
			}
		},
		checktextdomain: {
			options: {
				text_domain: 'rtbiz-helpdesk', //Specify allowed domain(s)
				keywords: [ //List keyword specifications
					'__:1,2d',
					'_e:1,2d',
					'_x:1,2c,3d',
					'esc_html__:1,2d',
					'esc_html_e:1,2d',
					'esc_html_x:1,2c,3d',
					'esc_attr__:1,2d',
					'esc_attr_e:1,2d',
					'esc_attr_x:1,2c,3d',
					'_ex:1,2c,3d',
					'_n:1,2,4d',
					'_nx:1,2,4c,5d',
					'_n_noop:1,2,3d',
					'_nx_noop:1,2,3c,4d'
				]
			},
			target: {
				files: [ {
					src: [
							'*.php',
							'**/*.php',
							'!node_modules/**',
							'!tests/**'
						], //all php
					expand: true
				} ]
			}
		},
		makepot: {
			target: {
				options: {
					cwd: '.', // Directory of files to internationalize.
					domainPath: 'languages/', // Where to save the POT file.
					exclude: [ 'node_modules/*', 'tests' ], // List of files or directories to ignore.
					mainFile: 'rtbiz-helpdesk.php', // Main project file.
					potFilename: 'rtbiz-helpdesk.po', // Name of the POT file.
					potHeaders: { // Headers to add to the generated POT file.
						poedit: true, // Includes common Poedit headers.
						'Last-Translator': 'rtBiz <rtbiz@rtbiz.io>',
						'Language-Team': 'rtBiz <rtbiz@rtbiz.io>',
						'report-msgid-bugs-to': 'http://community.rtcamp.com/',
						'x-poedit-keywordslist': true // Include a list of all possible gettext functions.
					},
					type: 'wp-plugin', // Type of project (wp-plugin or wp-theme).
					updateTimestamp: true // Whether the POT-Creation-Date should be updated without other changes.
				}
			}
		},

		// Watch for hanges and trigger compass and uglify
		// Ref. https://npmjs.org/package/grunt-contrib-watch
		watch: {
			compass: { files: [ '**/*.{scss,sass}' ],
				tasks: [ 'compass' ]
			},
			uglify: {
				files: [ '<%= uglify.frontend.src %>', '<%= uglify.backend.src %>', '<%= uglify.support.src %>','<%= uglify.shortcode.src %>'  ],
				tasks: [ 'uglify' ]
			}
		}
	} );

	// Register Task
	grunt.registerTask( 'default', [ 'checktextdomain', 'makepot', 'watch' ] );
};
