module.exports = function(grunt) {
	grunt.initConfig({
		pkg: grunt.file.readJSON('package.json'),
		concat: {
			bootstrap: {
				options: {
					process: function(content) {
						return content
							.replace(/'fade/g, '\'bwp-fade')
							.replace(/'\.modal/g, '\'.bwp-modal')
							.replace(/'modal-open/g, '\'bwp-modal-open')
							.replace(/'modal-backdrop/g, '\'bwp-modal-backdrop')
							.replace(/'modal-scrollbar-measure/g, '\'bwp-modal-scrollbar-measure')
							.replace(/'\.close/g, '\'.bwp-close')
							.replace(/'\.tooltip/g, '\'.bwp-tooltip')
							.replace(/class="tooltip/g, 'class="bwp-tooltip')
							.replace(/'\.popover/g, '\'.bwp-popover')
							.replace(/class="popover/g, 'class="bwp-popover')
							.replace(/'\.arrow/g, '\'.bwp-arrow')
							.replace(/class="arrow/g, 'class="bwp-arrow')
						;
					}
				},
				files: [{
					src: [
						'bower_components/bootstrap/js/transition.js',
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
							.replace(/\.fade/g, '.bwp-fade')
							.replace(/\.collapse/g, '.bwp-collapse')
							.replace(/\.collapsing/g, '.bwp-collapsing')
							.replace(/\.close/g, '.bwp-close')
							.replace(/\.modal/g, '.bwp-modal')
							.replace(/\.btn/g, '.bwp-btn')
							.replace(/\.tooltip/g, '.bwp-tooltip')
							.replace(/\.arrow/g, '.bwp-arrow')
							.replace(/\.popover/g, '.bwp-popover')
						;
					}
				},
				expand: true,
				cwd: 'bower_components/bootstrap/less',
				src: [
					'close.less',
					'component-animations.less',
					'modals.less',
					'tooltip.less',
					'popovers.less'
				],
				dest: 'assets/vendor/bootstrap/less'
			},
			bootbox: {
				options: {
					process: function(content) {
						return content
							.replace(/modal-/g, 'bwp-modal-')
							.replace(/bootbox modal/g, 'bootbox bwp-modal')
							.replace(/bootbox-close-button close/g, 'bootbox-close-button bwp-close')
							.replace(/"btn-primary"/g, '"bwp-btn-primary button-primary"')
							.replace(/\.btn-primary/g, '.bwp-btn-primary')
							.replace(/"btn-default"/g, '"bwp-btn-default button-secondary"')
							.replace(/\.btn-default/g, '.bwp-btn-default')
							.replace(/class=\'btn/g, 'class=\'bwp-btn')
							.replace(/"<button/g, '" <button')
						;
					}
				},
				src: 'bower_components/bootbox.js/bootbox.js',
				dest: 'assets/vendor/bootbox.js/bootbox.js'
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
			},
			inputmask: {
				src: 'bower_components/jquery.inputmask/dist/jquery.inputmask.bundle.js',
				dest: 'assets/vendor/inputmask/jquery.inputmask.bundle.js'
			}
		},
		uglify: {
			options: {
				mangle: true
			},
			common: {
				files: {
					'assets/option-page/dist/js/common.min.js': [
						'assets/option-page/js/common.js'
					]
				}
			},
			op: {
				files: {
					'assets/option-page/dist/js/op.min.js': [
						'assets/option-page/js/popover.js',
						'assets/option-page/js/toggle.js',
						'assets/option-page/js/op.js'
					]
				}
			},
			select2: {
				files: {
					'assets/vendor/select2/js/select2.min.js': [
						'assets/vendor/placeholders/placeholders.jquery.js',
						'assets/vendor/select2/js/select2.js'
					]
				}
			},
			bootstrap: {
				files: {
					'assets/vendor/bootstrap/js/bootstrap.min.js': [
						'assets/vendor/bootstrap/js/bootstrap.js',
					]
				}
			},
			bootstrap_modal: {
				files: {
					'assets/option-page/dist/js/modal.min.js': [
						'assets/vendor/bootbox.js/bootbox.js',
						'assets/option-page/js/bootbox.js',
						'assets/option-page/js/modal.js'
					]
				}
			},
			inputmask: {
				files: {
					'assets/vendor/inputmask/jquery.inputmask.bundle.min.js': [
						'assets/vendor/inputmask/jquery.inputmask.bundle.js'
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

	grunt.registerTask('default', ['copy-assets', 'minify']);
	grunt.registerTask('copy-assets', ['copy', 'concat']);
	grunt.registerTask('minify', ['uglify', 'cssmin']);
};
