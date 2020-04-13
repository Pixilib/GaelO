/**
 Copyright (C) 2018-2020 KANOUN Salim
 This program is free software; you can redistribute it and/or modify
 it under the terms of the Affero GNU General Public v.3 License as published by
 the Free Software Foundation;
 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 Affero GNU General Public Public for more details.
 You should have received a copy of the Affero GNU General Public Public along
 with this program; if not, write to the Free Software Foundation, Inc.,
 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA
 */

class DicomUploadView {
	constructor(config, selectorDom) {
		this.config = config;
		this.dom = $(selectorDom);

		// Zipping progression values (%) (needed for the progress bar)
		this.zippingProgress = [];

		// Upload progression values (%) (needed for the progress bar)
		this.uploadProgress = [];

		this.initHTML();

		this.initStatusInfo();

		this.initAlert();

		// Init 'ignored files panel'
		this.ignoredFilesPanel = new IgnoredFilesPanel();
		// Bootstrap link between the badge and the modal
		$('#du-ignored-badge').attr('data-toggle', 'modal');
		$('#du-ignored-badge').attr('data-target', '#du-ignored-files-modal');

		// Init 'studies panel'
		this.studiesPanel = new StudiesPanel(this.config);

		// Init 'series panel'
		this.seriesPanel = new SeriesPanel();


		// Init 'patient panel'
		if (this.config.multiImportMode) {
			this.patientPanel = new SelectPatientPanel();
		} else {
			this.patientPanel = new CheckPatientPanel();
		}

		this.initControls();

	}

	initHTML() {
		this.dom.append(`
			<section id="du-drop-zone">
				Drag & drop files here
			</section>

			<section id="du-status-info"></section>

			<section id="du-alert"></section>
			
			<section id="du-studies"></section>

			<section class="modal fade" id="du-patient" tabindex="-1" role="dialog" aria-labelledby="du-patientTitle" aria-hidden="true"></section>

			<section id="du-series"></section>

			<section id="du-controls"></section>
		`);
	}


	initStatusInfo() {
		$('#du-status-info').append(`
			<span id="du-loaded-badge" class="badge">
				<span id="nb-files-loaded"></span> File(s) loaded
			</span>

			<span id="du-parsed-badge" class="badge">
				<span id="nb-files-parsed"></span> File(s) parsed
			</span>

			<span id="du-ignored-badge" class="badge">
				<span id="nb-files-ignored"></span> File(s) ignored (Click to show)
			</span>

			<span id="du-status-info-text"></span>

			<div id="du-ignored-files-panel"></div>
		`);

		this.statusInfo = {
			dom: $('#du-status-info'),
			domInfoText: $('#du-status-info-text'),
			badges: {},

			hide: function () {
				this.dom.attr('hidden', '');
			},

			show: function () {
				this.dom.removeAttr('hidden');
			},

			addBadge: function (name, selectorDom, value = 0) {
				let newBadge = {
					value: value,
					dom: $(selectorDom),
					update: function () {
						this.dom.text(this.value);
					}
				};
				this.badges[name] = newBadge;
			},

			set(nameBadge, value) {
				this.badges[nameBadge].value = value;
				this.update();
			},

			update: function () {
				if (this.badges['nbFilesLoaded'].value === 0) {
					this.hide();
				} else {
					this.show();
				}
				for (let b in this.badges) {
					this.badges[b].update();
				};
			},

			setInfo: function (info) {
				this.domInfoText.text(info);
				this.domInfoText.removeClass();
				this.domInfoText.addClass('info');
			},
			setWarn: function (info) {
				this.domInfoText.text(info);
				this.domInfoText.removeClass();
				this.domInfoText.addClass('warn');
			},
			setError: function (info) {
				this.domInfoText.text(info);
				this.domInfoText.removeClass();
				this.domInfoText.addClass('error');
			},
			setSuccess: function (info) {
				this.domInfoText.text(info);
				this.domInfoText.removeClass();
				this.domInfoText.addClass('success');
			},

			getIgnoredFilesBadgeDom: function () {
				return $('#du-ignored-badge');
			}
		};

		this.statusInfo.hide();
		this.statusInfo.addBadge('nbFilesLoaded', $("#nb-files-loaded"));
		this.statusInfo.addBadge('nbFilesParsed', $("#nb-files-parsed"));
		this.statusInfo.addBadge('nbFilesIgnored', $("#nb-files-ignored"));
	}


	initAlert() {
		this.alert = {
			dom: $('#du-alert'),
			maxHeight: 7,

			hide: function () {
				this.dom.attr('hidden', '');
			},
			show: function () {
				this.dom.removeAttr('hidden');
			},
			clear: function () {
				this.dom.empty();
			},
			add: function (type, html) {
				this.dom.append(`
					<div class="alert alert-${type} alert-dismissible fade show" role="alert">
						${html}
						<button type="button" class="close" data-dismiss="alert" aria-label="Close">
							<span aria-hidden="true">&times;</span>
						</button>
					</div>
				`);
				if ($('#du-alert').height() >= 210) {
					$('#du-alert').css('max-height','auto');
					$('#du-alert').css('overflow','auto');
				} else {
					$('#du-alert').css('max-height','210px');
					$('#du-alert').css('overflow','visible');
				}
				$('#du-alert').scrollTop(9e9);
			},
			set: function (type, html) {
				this.clear();
				this.add(type, html);
			}
		}
	}


	initControls() {
		$('#du-controls').append(`
			<button id="du-upload" class="btn btn-success">Upload</button>

			<div>
				<div id="du-prog-bar-zipping" class="progress">
					<div class="progress-bar progress-bar-striped bg-dark"></div>
				</div>

				<div id="du-prog-bar-upload" class="progress">
					<div class="progress-bar progress-bar-striped"></div>
				</div>
			</div>
		`);

		this.controls = {
			dom: $('#du-controls'),

			hide: function () {
				this.dom.attr('hidden', '');
			},
			show: function () {
				this.dom.removeAttr('hidden');
			}
		};

		this.controls.hide();

		// ~
		this.btnUpload = {
			dom: $('#du-upload'),

			enable: function () {
				this.dom.removeAttr('disabled');
			},
			disable: function () {
				this.dom.attr('disabled', '');
			}
		};

		// ~

		this.progBarZipping = createProgBar('#du-prog-bar-zipping > div');
		this.progBarUpload = createProgBar('#du-prog-bar-upload > div');
		this.progBarZipping.set(0, '');
		this.progBarUpload.set(0, '');

		function createProgBar(DOMselector) {
			return {
				dom: $(DOMselector),
				value: 0,

				// When the progress bar monitores multiple tasks, the value
				// displayed is the average value of all the tasks progresses.
				// The tasks require a task index, and call setTaskProgress()
				// to update the progress bar.
				tasks: [],

				setTaskProgress: function (taskIndex, value, text = '') {
					this.tasks[taskIndex] = value;

					// Compute new value
					let newValue = 0;
					for (let taskProgress of this.tasks) {
						newValue += taskProgress;
					}
					newValue /= this.tasks.length;

					// Update progress bar
					this.set(newValue, `${text} ${newValue.toFixed(0)} %`);
				},

				set: function (value, text) {
					this.value = value;
					this.dom.css('width', value + '%');
					this.dom.text(text);
				}
			}
		}
	}


}