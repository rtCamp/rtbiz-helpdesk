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
					sassDir: 'app/assets/admin/css/sass/',
					cssDir: 'app/assets/admin/css/'
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
					'app/assets/js/common.js',
					'app/assets/js/app.js',
					'app/assets/js/vendors/stickyfloat.js',
				    'app/assets/js/vendors/plupupload/plupload.full.min.js'
				],
				dest: 'app/assets/js/main.js'
			},
			backend: {
				src: [
					'app/assets/admin/js/vendors/moment.js',
					'app/assets/js/vendors/plupupload/plupload.full.min.js',
					'app/assets/admin/js/vendors/jquery.steps.js',
					'app/assets/admin/js/setup-wizard.js',
					'app/assets/js/common.js',
					'app/assets/admin/js/admin.js',
				    'app/assets/admin/js/rthd_plugin_check.js'
				],
				dest: 'app/assets/admin/js/admin-min.js'
			}
		},
		// Watch for hanges and trigger compass and uglify
		// Ref. https://npmjs.org/package/grunt-contrib-watch
		watch: {
			compass: { files: [ '**/*.{scss,sass}' ],
				tasks: [ 'compass' ]
			},
			uglify: {
				files: [ '<%= uglify.frontend.src %>', '<%= uglify.backend.src %>' ],
				tasks: [ 'uglify' ]
			}
		}
	} );

	// Register Task
	grunt.registerTask( 'default', [ 'watch' ] );
};
