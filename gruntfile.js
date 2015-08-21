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
                    'public/js/vendors/markdown/showdown-prettify.js',
					'public/js/zen-form.js'
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
                    'public/js/vendors/markdown/showdown-prettify.js',
					'public/js/zen-form.js'
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
	grunt.registerTask( 'default', [ 'watch' ] );
};
