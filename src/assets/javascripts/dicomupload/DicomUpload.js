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

 /**
	* Controller class
	*/
class DicomUpload {

	/**
	 * Instanciate a new dicom upload process
	 * @param {*} selectorDom HTML block which contains the dicom upload
	 * @param {*} customConfig config overrides
	 */
	constructor(selectorDom, customConfig) {
		// Declare default config
		this.config = {
			multiImportMode: false,
			expectedVisitsURL: '../../scripts/get_possible_import.php',
			validationScriptURL: '../../scripts/validate_dicom_upload.php',
			dicomsReceiptsScriptURL: '../../scripts/dicoms_receipts.php',
			isNewStudyURL: '../../scripts/is_new_study.php',
			alertMessageWhenNoVisitAwatingUpload: 'No visit is awaiting series upload. Please create a new visit by clicking on the patient in the <a id="redirect-to-investigator" href="#">patient tree</a>.',
			minNbOfInstances: 30,
			idVisit: null,
			refreshRateProgBar: 200,
			callbackOnComplete: null,
			callbackOnBeforeUnload: function (event) {
				event.preventDefault();
				event.returnValue = ''; // Needed for Chrome
			},
			callbackOnAbort: function(){
				refreshInvestigatorDiv()
			}
			
		}

		// Override custom config if set
		if (customConfig !== undefined) {
			for (let elmt in this.config) {
				if (customConfig[elmt] !== undefined) {
					this.config[elmt] = customConfig[elmt];
				}
			}
		}

		// Detect errors in config
		if (!this.config.multiImportMode && this.config.idVisit == null) {
			throw Error('Dicom Upload Js: On single import mode and idVisit is null. You must specify an idVisit or get in multiple import mode');
		}

		this.isUploading = false;
		this.isInUse = false; // The user has entered data
		window.dicomUploadInUse=false;

		// Javascript intervals references need to be global variable
		// in order to clear them if upload is aborted prematurely
		this.intervals = {
			updateValProgress: []
		};

		// ResumableJS objects that perform uploading
		this.resumables = [];

		this.v = new DicomUploadView(this.config, selectorDom);
		this.m = new DicomUploadModel(this.config);

		DAO.fetchExpectedVisits(this.config, (visits) => {
			if (visits.length === 0) {
				this.m.dz.dom.attr('hidden', '');
				this.v.alert.add('info', this.config.alertMessageWhenNoVisitAwatingUpload);
				$('#redirect-to-investigator').on('click', function() {
					refreshInvestigatorDiv();
				});
			}
			this.m.expectedVisits = visits;
		});

		this.startListeners();
	}

	// ~

	callbackOnComplete() {
		this.isInUse = false;
		window.dicomUploadInUse=false;
		this.clearMemory();
		// Allow page changing
		window.removeEventListener('beforeunload', this.config.callbackOnBeforeUnload);
		// Allow ajax div loading
		$(document).off('ajaxSend');

		if (typeof this.config.callbackOnComplete == 'function') {
			this.config.callbackOnComplete();
		}
	}

	// ~

	clearMemory() {
		for (let i1 in this.intervals) {
			if (Array.isArray(this.intervals[i1])) {
				for (let i2 of this.intervals[i1]) {
					clearInterval(i2);
				}
			} else {
				clearInterval(this.intervals[i1]);
			}
		}
		if (this.v != undefined) {
			this.v = null;
		}
		if (this.m != undefined) {
			for (let st of this.m.studies) {
				st.isUploadAborted = true;
				st.clearAllSeries();
			}
			this.m = null;
		}
	}

	// ~

	/**
	 * Set listeners on page changing and page refreshing
	 * The user will be asked to confirm before updating the page
	 */
	preventAjaxDivLoading() {
		// Prevent page changing
		window.addEventListener('beforeunload', this.config.callbackOnBeforeUnload);

		// Prevent ajax div loading
		$(document).ajaxSend((evt, request, settings) => {
			request.abort();
			alertify.confirm('Close?', 'Your upload will be lost. Do you want to <strong>cancel the upload</strong>? </br> In that case, Click OK and redo your action.', () => {
				for (let r of this.resumables) {
					r.cancel();
				}
				this.disableControllers();
				$('#uploadDicom').addClass('du-deactivated');
				this.clearMemory();
				$(document).off('ajaxSend');
				this.config.callbackOnAbort();
			}, () => { });
		});
	}

	// ~

	startListeners() {

		this.m.dz.on('drop', () => {
			this.m.dz.dom.text('Loading files');
			this.m.dz.dom.addClass('dz-parsing');
			this.v.statusInfo.setInfo('Loading files');
		});

		this.m.dz.on('dragover', () => {
			if (this.m.dz.active)
				this.m.dz.dom.text('Drop here');
		});

		this.m.dz.on('dragleave', () => {
			if (this.m.dz.active)
				this.m.dz.dom.text('Drag & drop files here');
		});


		this.m.dz.on('addedfile', (file) => {
			this.v.controls.hide();
			this.m.loadedFiles.push(file);
			this.m.queuedFiles.push(file);
			this.v.statusInfo.setInfo('Loading & parsing files');
			this.read(file);
		});


		this.m.dz.dom[0].addEventListener('parsing', () => {
			let parsingProgress = (this.m.parsedFiles.length + this.m.ignoredFiles.length) / this.m.loadedFiles.length;
			this.m.dz.dom.text(`Parsing files... (${(parsingProgress * 100).toFixed(0)} %)`);
			this.m.dz.dom.addClass('dz-parsing');
			this.updateViewStatusInfo();
		});

		this.m.dz.dom[0].addEventListener('parsingEnd', () => {
			this.m.dz.dom.text('Drag & drop files here');
			this.m.dz.dom.removeClass('dz-parsing');
			this.m.checkStudies();

			// Display help alert if all the studies need to check patient
			if (this.m.validStudies.length == 0 && this.m.studies.filter((st) => st.hasWarning('notExpectedVisit') == this.m.studies.length)) {
				this.v.alert.set('warning', 'Please, check/select the patient. The imported patient informations do not correspond with the expected ones.');
			}

			this.updateViewControls();
			this.m.dz.reloadWebKitDir();
			this.updateViewStatusInfo();
			if (this.v.studiesPanel.selected == null && this.m.studies[0].studyInstanceUID != undefined) {
				// Select first study
				this.v.studiesPanel.selected = this.m.studies[0].studyInstanceUID;
			}
			this.v.studiesPanel.update(this.m.studies);
			if (!this.isInUse) {
				this.isInUse = true;
				window.dicomUploadInUse=true;
				this.preventAjaxDivLoading();
			}
		});

		// ~

		this.v.statusInfo.getIgnoredFilesBadgeDom().on('click', () => {
			this.v.ignoredFilesPanel.update(this.m.ignoredFiles);
		});

		// ~

		this.v.studiesPanel.dom[0].addEventListener('selectStudy', (evt) => {
			let study = evt.detail.study;
			this.v.seriesPanel.update(study);
			this.v.studiesPanel.selected = study.studyInstanceUID;
			this.v.studiesPanel.updateWarnings(study);
			if (this.isUploading) {
				this.disableControllers();
			}
		});

		this.v.studiesPanel.dom[0].addEventListener('ignoreWarning', (evt) => {
			let study = evt.detail.study;
			study.ignoreWarning(evt.detail.warningName);
			this.m.checkStudies();
			this.v.studiesPanel.updateWarnings(study);
			this.updateViewControls();
			this.v.studiesPanel.update(this.m.studies);
		});

		this.v.studiesPanel.dom[0].addEventListener('considerWarning', (evt) => {
			let study = evt.detail.study;
			study.considerWarning(evt.detail.warningName);
			this.m.checkStudies();
			this.v.studiesPanel.updateWarnings(study);
			this.updateViewControls();
			this.v.studiesPanel.update(this.m.studies);
		});

		this.v.studiesPanel.dom[0].addEventListener('checkboxChange', (evt) => {
			let study = evt.detail.study;
			if (evt.detail.isChecked) {
				this.m.queueStudy(study);
			} else {
				this.m.dequeueStudy(study);
			}

			// If uncompletely queued study then dequeue study
			if (study.hasQueuedSeries() && !study.hasAllSeriesQueued()) {
				this.m.dequeueStudy(study);
			}

			// If the study is queued and there is no queued series
			// then queue all the series by default
			if (study.isQueued && !study.hasQueuedSeries()) {
				for (let sr of study.validSeries) {
					study.queueSerie(sr)
				}
			}

			this.v.studiesPanel.update(this.m.studies);
			this.v.seriesPanel.update();
			this.updateViewControls();
		});

		this.v.studiesPanel.dom[0].addEventListener('patpClick', (evt) => {
			let study = evt.detail.study;
			this.v.patientPanel.reset();
			this.v.patientPanel.update(study, this.m.expectedVisits);
		});

		this.v.studiesPanel.dom[0].addEventListener('patRemoveClick', (evt) => {
			let study = evt.detail.study;
			study.warnings['notExpectedVisit'].ignore = false;
			study.visit.isSelected = false;
			study.visit = null;
			this.m.checkStudies();

			this.v.studiesPanel.update(this.m.studies);
			this.v.seriesPanel.update();
			this.updateViewControls();
		});

		// ~

		this.v.seriesPanel.dom[0].addEventListener('selectSerie', (evt) => {
			let serie = evt.detail.serie;
			this.v.seriesPanel.selected = serie.seriesInstanceUID;
			this.v.seriesPanel.updateWarnings(serie);
			if (this.isUploading) {
				this.disableControllers();
			}
		});

		this.v.seriesPanel.dom[0].addEventListener('ignoreWarning', (evt) => {
			let serie = evt.detail.serie;
			serie.ignoreWarning(evt.detail.warningName);
			this.m.checkStudies();
			this.v.seriesPanel.updateWarnings(serie);
			this.v.studiesPanel.updateWarnings(this.v.seriesPanel.study);
			this.updateViewControls();
			this.v.studiesPanel.update(this.m.studies);
			this.v.seriesPanel.update();
		});

		this.v.seriesPanel.dom[0].addEventListener('considerWarning', (evt) => {
			let serie = evt.detail.serie;
			serie.considerWarning(evt.detail.warningName);
			this.m.checkStudies();
			this.v.seriesPanel.updateWarnings(serie);
			this.v.studiesPanel.updateWarnings(this.v.seriesPanel.study);
			this.updateViewControls();
			this.v.studiesPanel.update(this.m.studies);
			this.v.seriesPanel.update();
		});

		this.v.seriesPanel.dom[0].addEventListener('checkboxChange', (evt) => {
			let serie = evt.detail.serie;
			let study = evt.detail.study;
			if (evt.detail.isChecked) {
				study.queueSerie(serie);
			} else {
				study.dequeueSerie(serie);
			}

			if (study.hasQueuedSeries()) {
				this.m.queueStudy(study);
			} else {
				this.m.dequeueStudy(study);
			}

			this.v.studiesPanel.update(this.m.studies);
			this.v.seriesPanel.update();
			this.updateViewControls();
		});

		// ~

		this.v.patientPanel.dom[0].addEventListener('confirm', (evt) => {
			let study = evt.detail.study;
			study.warnings['notExpectedVisit'].ignore = !evt.detail.hasWarnings;

			if (!this.config.multiImportMode) {
				if (typeof this.config.idVisit != 'number') {
					throw Error('Config: Visit ID is not correctly set while in single import mode: ' + this.config.idVisit);
				}
				this.m.expectedVisits[0].isSelected = true;
				study.visit = this.m.expectedVisits[0];
			} else {
				evt.detail.visit.isSelected = true;
				study.visit = evt.detail.visit;
			}

			this.m.checkStudies();

			// Display help alert if all the studies need to check patient
			if (this.m.validStudies.length == 0 && this.m.studies.filter((st) => st.hasWarning('notExpectedVisit')) == this.m.studies.length) {
				this.v.alert.set('warning', 'Please, check/select the patient. The imported patient informations do not correspond with the expected ones.');
			} else {
				this.v.alert.clear();
			}

			// If the valid study is queued and there is no queued
			// series then queue all the series by default
			if (this.m.validStudies.includes(study) && study.queuedSeries.length === 0) {
				for (let sr of study.validSeries) {
					study.queueSerie(sr)
				}
			}

			if (study.hasQueuedSeries()) {
				this.m.queueStudy(study);
			} else {
				this.m.dequeueStudy(study);
			}

			this.v.studiesPanel.update(this.m.studies);
			this.v.seriesPanel.update();
			this.updateViewControls();
		});

		// ~

		this.v.btnUpload.dom.on('click', () => {
			this.v.btnUpload.disable();
			this.m.dz.setActive(false);
			try {
				if (this.m.queuedStudies.length === 0) {
					this.v.btnUpload.enable();
					this.m.dz.setActive(true);
					this.v.statusInfo.setWarn('You must select a valid study to upload.');
					alertifyWarning('You must select a valid study to upload.');
					throw `Empty queue.`;
				}

				if (!this.config.multiImportMode) {
					if (this.m.queuedStudies.length !== 1) {
						this.v.btnUpload.enable();
						this.m.dz.setActive(true);
						this.v.statusInfo.setWarn('You cannot upload more than one study on single import mode.');
						alertifyWarning('You cannot upload more than one study on single import mode.');
						throw `Unvalid queue length.`;
					}
				}

				this.v.alert.set('info', 'Uploading... Please do not leave this page.');
				alertifyWarning('Uploading... Please do not leave this page.');
				this.isUploading = true;
				this.disableControllers();
				this.sendFiles(this.config, this.m.queuedStudies);

			} catch (e) {
				console.warn(e);
			}
		});

		

	}

	disableControllers() {
		$('#du-studies button').attr('disabled', '');
		$('#du-studies input[type="checkbox"]').attr('disabled', '');
		$('#du-series button').attr('disabled', '');
		$('#du-series input[type="checkbox"]').attr('disabled', '');
	}

	// ~

	updateViewStatusInfo() {
		this.v.statusInfo.show();
		this.v.statusInfo.set('nbFilesLoaded', this.m.loadedFiles.length);
		this.v.statusInfo.set('nbFilesParsed', this.m.parsedFiles.length);
		this.v.statusInfo.set('nbFilesIgnored', this.m.ignoredFiles.length);

		if (this.m.queuedFiles.length > 0) {
			this.v.statusInfo.setInfo('Loading & parsing files');
		} else {
			this.v.statusInfo.setInfo('');
		}
	}

	updateViewControls() {
		if (this.m.hasQueuedStudies()) {
			this.v.controls.show();
		} else {
			this.v.controls.hide();
		}
	}

	// ~

	/**
	 * Read and parse dicom file
	 */
	read(file) {
		const reader = new FileReader();
		reader.readAsArrayBuffer(file);
		reader.onload = () => {
			// Retrieve file content as Uint8Array
			const arrayBuffer = reader.result;
			const byteArray = new Uint8Array(arrayBuffer);

			try {
				// Try to parse as dicom file
				this.readAsDicomFile(file, byteArray);

				if (this.m.queuedFiles.length == 0) {
					Util.dispatchEventOn('parsingEnd', this.m.dz.dom[0]);
				} else {
					Util.dispatchEventOn('parsing', this.m.dz.dom[0]);
				}

			} catch (e) {
				// Only catch 'Not a DICOM' error
				if (e == 'Not a DICOM') {
					// Try to parse as zip file
					this.readAsZipFile(file, byteArray)
				}
				
			}
		}
	}

	/**
	 * Try to parse 'byteArray' as a dicom file
	 */
	readAsDicomFile(file, byteArray) {
		try {

			let parsedDicom = dicomParser.parseDicom(byteArray)
			let dicomFile = new DicomFile(file, parsedDicom);

			this.m.register(dicomFile);
			this.m.move(file, 'queuedFiles', 'parsedFiles');

		} catch (e) {
			console.warn(e);
			if(e.includes('dicomParser')) throw 'Not a DICOM'
			file.ignoredBecause = e;
			this.m.move(file, 'queuedFiles', 'ignoredFiles');
		}
	}

	/**
	 * Explore zip file and virtually drop its content
	 * into the drop zone 'added file' list
	 */
	readAsZipFile(file, byteArray) {
		let zip = new JSZip();
		zip.loadAsync(byteArray)
			.then(() => {
				// Remove the zip file from the loaded files
				this.m.remove(file, 'loadedFiles');
				this.m.remove(file, 'queuedFiles');

				for (let elmt in zip.files) {
					elmt = zip.files[elmt];
					// Check if it is a file or a directory
					if (!elmt.dir) {
						// Decompress file
						elmt.async('blob').then((data) => {
							let elmtFile = new File([data], elmt.name);
							this.m.dz.obj.addFile(elmtFile);
						});
					}
				}
			})
			.catch((e) => {
				console.log(e)
				//console.warn('Not a ZIP file: ' + file.name + ' -> This file will be ignored.');
				file.ignoredBecause = 'Not a ZIP or a DICOM file.';
				this.m.move(file, 'queuedFiles', 'ignoredFiles');

				// Dispatch 'endParsing' event to trigger the controller
				if (this.m.queuedFiles.length == 0) {
					Util.dispatchEventOn('parsingEnd', this.m.dz.dom[0]);
				} else {
					Util.dispatchEventOn('parsing', this.m.dz.dom[0]);
				}
			});
	}

	// ~

	/**
	 * Zip study and upload thanks to resumableJS
	 */
	async sendFiles() {
		// An index is attributed for each uploading study
		let indexStudyToUpload = 0;

		// Clear memory
		for (let st of this.m.studies) {
			if (!st.isQueued) {
				st.clearAllSeries();
			} else {
				st.clearNonQueuedSeries();
			}
		}

		// Send queued studies
		for (let st of this.m.queuedStudies) {
			this.prepareZip(st, indexStudyToUpload);
			indexStudyToUpload++;

			// Add an element in the upload/zipping progress values array
			// and in the tasks list of the progresses bar
			// (needed for the progresses bar display)
			this.v.uploadProgress.push(0);
			this.v.zippingProgress.push(0);
			this.v.progBarUpload.tasks.push(0);
			this.v.progBarZipping.tasks.push(0);

			// Each study has its own Resumable obj
			this.resumables.push(new Resumable({
				target: this.config.dicomsReceiptsScriptURL,
				chunkSize: 1 * 1024 * 1024,
				testChunks: true
			}));
		}

		const nbUploads = indexStudyToUpload;

		// Update progress bar on user interface
		this.intervals.updateProgBar = setInterval(() => {

			for (let i = 0; i < nbUploads; i++) {
				let value;

				value = this.v.zippingProgress[i];
				this.v.progBarZipping.setTaskProgress(i, value, 'Zipping:');

				value = this.v.uploadProgress[i];
				this.v.progBarUpload.setTaskProgress(i, value, "Upload:");
			}

			if (this.v.progBarUpload.value >= 100) {
				clearInterval(this.intervals.updateProgBar);
				this.v.statusInfo.setSuccess('Successfully sent.');
				this.v.alert.set('success', 'Files successfully sent. The server is processing the series.');
				alertifySuccess('Files successfully sent');
				this.callbackOnComplete();
			}

		}, this.config.refreshRateProgBar);

	}

	// ~

	/**
	 * Add the dicom files contained in the queued series of 'study'
	 * to a JSZip object
	 */
	prepareZip(study, indexStudyToUpload) {
		this.v.statusInfo.setInfo('Zipping files...');

		// Create a new jszip object with folders & dicom files
		let jszip = new JSZip();

		// Add all the instances of the queued series in the JSZip obj
		let counter = 0;
		for (let sr of study.queuedSeries) {
			for (let inst of sr.instances) {

				let reader = new FileReader();
				reader.readAsArrayBuffer(inst.dicomFile.originalFile);

				reader.onload = () => {
					if (study.isUploadAborted) {
						throw 'Study #' + (indexStudyToUpload + 1) + ': Upload aborted';
					}

					const dfileByteArray = new Uint8Array(reader.result);

					// Check SOP Instance UID
					const dFile = new DicomFile(null, dicomParser.parseDicom(dfileByteArray));
					if (dFile.getSOPInstanceUID() != inst.dicomFile.getSOPInstanceUID()) {
						// Abort study upload
						this.abortStudyUpload(study, indexStudyToUpload, 'Could not read the files on your computer: Incoherent DICOM Files data. Did you move, rename or delete them?');
						throw `Study #${indexStudyToUpload + 1} upload aborted`;
					}

					// Concatenate header and pixel data
					const pixelDataOffset = inst.getHeaderByteArray().length;
					const pixelData = dfileByteArray.slice(pixelDataOffset, 9e9);
					const finalByteArray = Util.concat([inst.getHeaderByteArray(), pixelData]);

					// Add file to JSZip object
					jszip.file(inst.getFilePath(), finalByteArray);

					counter++;
					if (counter == study.getNbQueuedInstances()) {
						// No more file to add, compression can begin
						this.compressZip(jszip, study, indexStudyToUpload);
					}
				};

				reader.onerror = () => {
					this.abortStudyUpload(study, indexStudyToUpload, 'Could not read the files on your computer. Did you move, rename or delete them?');
					throw `Study #${indexStudyToUpload + 1} upload aborted`;
				};
			}
		}
	}

	compressZip(jszip, study, indexStudyToUpload) {
		// Check if study upload is not aborted
		if (study.isUploadAborted) {
			throw 'Study #' + (indexStudyToUpload + 1) + ': Upload aborted';
		}

		// Generate a blob zip file with the jszip object
		let promisedZipFile = jszip.generateAsync(
			// Zipping options
			{
				type: "uint8array",
				compression: "DEFLATE",
				compressionOptions: {
					level: 3,
					streamFiles: true
				}
			},
			// Callback on update
			(metadata) => {
				this.v.zippingProgress[indexStudyToUpload] = metadata.percent;
			}
		);

		promisedZipFile.then((blob) => {
			this.v.statusInfo.setInfo('');
			let fileName = `dicom-${Date.now()}-${study.visit.idVisit}.zip`;
			let zipFile = blob;
			this.upload(zipFile, fileName, 'application/zip', indexStudyToUpload, study);
		});
	}

	// ~

	/**
	 * Upload the data to the server & send file info for validation
	 */
	upload(data, fileName, type, indexStudyToUpload, study) {
		// Check if study upload is not aborted
		if (study.isUploadAborted) {
			throw 'Study #' + (indexStudyToUpload + 1) + ': Upload aborted';
		}

		let file = new Blob([data], { type: type });
		file.name = fileName;

		let r = this.resumables[indexStudyToUpload];
		r.addFile(file);

		r.on('fileAdded', () => {
			this.v.statusInfo.setInfo('Uploading...');
			r.upload();
		});

		r.on('progress', () => {
			this.v.uploadProgress[indexStudyToUpload] = r.progress() * 100;
		});

		r.on('complete', () => {
			study.hasSentRequestConfirmation = true;
			DAO.requestConfirmation(
				this.config,
				fileName,
				study.visit.idVisit,
				study.studyInstanceUID,
				study.getNbQueuedInstances(),
				study.getOrthancID()
			);
			// Clear memory
			study.clearAllSeries();
		});
	}

	// ~

	abortStudyUpload(study, indexStudyToUpload, message) {
		if (!study.isUploadAborted) {
			study.isUploadAborted = true;
			this.v.alert.add('danger', `<strong>Failure while uploading Study #${indexStudyToUpload + 1}</strong>: ${message}`);
			alertifyError(`Failure while uploading Study #${indexStudyToUpload + 1}`);
		}
		let isUploadTotallyAborted = true;
		for (let qSt of this.m.queuedStudies) {
			if (!qSt.isUploadAborted || qSt.isUploadAborted == undefined) {
				isUploadTotallyAborted = false;
			}
		}
		if (isUploadTotallyAborted) {
			this.v.alert.add('danger', `<strong>Upload has failed. Please, try again.</strong>`);
		}
	}
}