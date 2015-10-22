module.exports = function(grunt) {
	grunt.initConfig({
		pkg: grunt.file.readJSON('package.json'),
		concat: {
			bootstrap: {
				options: {
					process: function(content) {
						return content
							.replace(/'\.modal/g, '\'.bwp-modal')
							.replace(/'modal-open/g, '\'bwp-modal-open')
							.replace(/'modal-backdrop/g, '\'bwp-modal-backdrop')
							.replace(/'modal-scrollbar-measure/g, '\'bwp-modal-scrollbar-measure')
							.replace(/'\.close/g, '\'.bwp-close')
							.replace(/'\.tooltip/g, '\'.bwp-tooltip')
							.replace(/class="tooltip/g, 'class="bwp-tooltip')
							.replace(/'\.popover/g, '\'.bwp-popover')
							.replace(/class="popover/g, 'class="bwp-popover')
						;
					}
				},
				files: [{
					src: [
						'bower_components/bootstrap/js/modal.js',
						'bower_components/bootstrap/js/tooltip.js',
						'bower_components/bootstrap/js/popover.js',
					],
					dest: 'assets/vendor/bootstrap/js/bootstrap.js'
				}]
			}
		},
		copy: {
			bootstrap: {
				options: {
					process: function(content) {
						return content
							.replace(/\.modal/g, '.bwp-modal')
							.replace(/\.btn/g, '.bwp-btn')
							.replace(/\.close/g, '.bwp-close')
							.replace(/\.tooltip/g, '.bwp-tooltip')
							.replace(/\.popover/g, '.bwp-popover')
						;
					}
				},
				expand: true,
				cwd: 'bower_components/bootstrap/less',
				src: [
					'close.less',
					'modals.less',
					'tooltip.less',
					'popovers.less'
				],
				dest: 'assets/vendor/bootstrap/less'
			},
			datatables: {
				expand: true,
				cwd: 'bower_components/datatables/media',
				src: '**/*.*',
				dest: 'assets/vendor/datatables',
			},
			placeholders: {
				expand: true,
				cwd: 'bower_components/placeholders/dist',
				src: '**/*.*',
				dest: 'assets/vendor/placeholders',
			},
			select2: {
				expand: true,
				cwd: 'bower_components/select2/dist',
				src: '**/*.*',
				dest: 'assets/vendor/select2',
			}
		},
		uglify: {
			options: {
				mangle: true
			},
			op: {
				files: {
					'assets/option-page/dist/js/op.min.js': [
						'assets/option-page/js/*.js',
						'!assets/option-page/js/paypal.js'
					]
				}
			},
			bootstrap: {
				files: {
					'assets/vendor/bootstrap/js/bootstrap.min.js': [
						'assets/vendor/bootstrap/js/bootstrap.js'
					]
				}
			}
		},
		cssmin: {
			op: {
				files: {
					'assets/option-page/dist/css/op.min.css': [
						'assets/option-page/css/style.css',
					]
				}
			}
		}
	});

	grunt.loadNpmTasks('grunt-contrib-copy');
	grunt.loadNpmTasks('grunt-contrib-concat');
	grunt.loadNpmTasks('grunt-contrib-uglify');
	grunt.loadNpmTasks('grunt-contrib-cssmin');

	grunt.registerTask('copy-assets', ['copy', 'concat']);
	grunt.registerTask('minify', ['uglify', 'cssmin']);
};
