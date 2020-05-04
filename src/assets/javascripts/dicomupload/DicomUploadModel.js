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

class DicomUploadModel {
	constructor(config) {
		this.config = config;

		// Drop zone object
		this.dz = new DicomDropZone('#du-drop-zone');

		// Files dropped in the drop zone
		this.loadedFiles = [];

		// Files waiting for processing (and decompressed zip content)
		this.queuedFiles = [];

		// Files that just have been successfully parsed
		this.parsedFiles = [];

		// Files that could not have been recognized as valid dicom file
		this.ignoredFiles = [];

		// ~

		// Studies list
		this.studies = [];


		// Studies which passed the checks successfully
		this.validStudies = [];

		// Studies which do not have critical warnings and
		// have both rejected series and valid series ready to upload
		this.incompleteStudies = [];

		// Studies which did not passed the checks
		this.rejectedStudies = [];


		// Files waiting for upload
		this.queuedStudies = [];

		// ~

		this.expectedVisits = [];

		// ~
	}

	// ~

	getStudy(studyInstanceUID) {
		for (let st of this.studies) {
			if (st.studyInstanceUID == studyInstanceUID) {
				return st;
			}
		}
		return null;
	}

	getSerie(seriesInstanceUID) {
		for (let st of this.studies) {
			for (let sr of st.series) {
				if (sr.seriesInstanceUID == seriesInstanceUID) {
					return sr;
				}
			}
		}
		return null;
	}

	getInstance(SOPInstanceUID) {
		for (let st of this.studies) {
			for (let sr of st.series) {
				for (let inst of sr.instances) {
					if (inst.SOPInstanceUID == SOPInstanceUID) {
						return inst;
					}
				};
			};
		};
		return null;
	}

	// ~

	isKnownStudy(dicomFile) {
		return this.getStudy(dicomFile.getStudyInstanceUID()) !== null;
	}

	isKnownSerie(dicomFile) {
		return this.getSerie(dicomFile.getSeriesInstanceUID()) !== null;
	}

	isKnownInstance(dicomFile) {
		return this.getInstance(dicomFile.getSOPInstanceUID()) !== null;
	}

	// ~

	queueStudy(study) {
		study.isQueued = true;
		this.put(study, 'queuedStudies');
	}

	dequeueStudy(study) {
		study.isQueued = false;
		this.remove(study, 'queuedStudies');
		study.dequeueAllSeries();
	}


	setStatusStudy(study, status) {
		study.status = status;
		this.remove(study, 'incompleteStudies');
		this.remove(study, 'rejectedStudies');
		this.remove(study, 'validStudies');
		switch (status) {
			case 'incomplete':
				this.put(study, 'incompleteStudies');
				break;
			case 'rejected':
				this.put(study, 'rejectedStudies');
				break;
			case 'valid':
				this.put(study, 'validStudies');
				break;
			default:
				//console.warn('Invalid Study State');
		}
	}

	// ~

	hasQueuedStudies() {
		for (let st of this.queuedStudies) {
			if (st.hasQueuedSeries()) {
				return true;
			}
		}
		return false;
	}

	// ~

	/**
   * Delete specific element from a given array
   */
	remove(elmt, fromArrName) {
		const index = this[fromArrName].indexOf(elmt);
		if (index > -1) {
			this[fromArrName].splice(index, 1);
		}
	}

	/**
	 * Move specific element from a given array to another
	 */
	move(elmt, fromArrName, toArrName) {
		const index = this[fromArrName].indexOf(elmt);
		if (index > -1) {
			this[fromArrName].splice(index, 1);
			this[toArrName].push(elmt);
		}
	}

	/**
	 * Push an element to an array
	 */
	put(elmt, toArrName) {
		if (!this[toArrName].includes(elmt)) {
			this[toArrName].push(elmt);
		}
	}

	// ~

	/**
	* Register the study, serie, instance of a dicom file if not known yet
	* @throws 'Secondary Capture Image Storage are not allowed.'
	* @throws 'Not expected visit.'
	* @throws 'DICOM file duplicates found'
	*/
	register(dicomFile) {

		// Check SOP Class UID is not Secondary Capture Image Storage
		if (dicomFile.isSecondaryCaptureImg()) {
			throw 'Secondary Capture Image Storage are not allowed.';
		}

		if(dicomFile.isDicomDir()){
			throw 'Dicomdir File, ignoring'
		}

		// Check if the study has already been registered (on client-side)
		if (!this.isKnownStudy(dicomFile)) {
			let st = new Study(
				dicomFile.getStudyInstanceUID(),
				dicomFile.getStudyDate(),
				dicomFile.getStudyDescription(),
				dicomFile.getStudyID(),
				dicomFile.getAccessionNumber(),
				dicomFile.getAcquisitionDate(),
				dicomFile.getPatientID(),
				dicomFile.getPatientName(),
				dicomFile.getPatientBirthDate(),
				dicomFile.getPatientSex()
			);

			this.studies.push(st);

			// Check if the study is not already registered in the server
			if (!DAO.fetchIsNewStudy(this.config, st.getOrthancID(), (isNew) => {
				if (isNew == 'false') {
					st.setWarning('isNotNewStudy', 'This study is already known by the server.');
				}
			}));
		}

		// Check if the series has already been registered
		if (!this.isKnownSerie(dicomFile)) {
			let study = this.getStudy(dicomFile.getStudyInstanceUID());
			study.series.push(new Serie(
				dicomFile.getSeriesInstanceUID(),
				dicomFile.getSeriesNumber(),
				dicomFile.getSeriesDate(),
				dicomFile.getSeriesDescription(),
				dicomFile.getModality()
			));
		}

		// Check if the instance has already been registered
		if (!this.isKnownInstance(dicomFile)) {
			const inst = new Instance(
				dicomFile.getSOPInstanceUID(),
				dicomFile.getInstanceNumber(),
				dicomFile
			);

			inst.dicomFile.anonymise();

			let serie = this.getSerie(dicomFile.getSeriesInstanceUID());
			serie.instances.push(inst);

		} else {
			throw 'Duplicates. This instance is already loaded.';
		}
	}

	// ~

	checkStudies() {
		for (let st of this.studies) {

			// Check if the study corresponds to the visits in wait for series upload
			let expectedVisit = this.findExpectedVisit(st);
			if (expectedVisit === undefined) {
				st.setWarning('notExpectedVisit', 'You should check/select the patient. The imported study informations do not match with the expected ones.', true, false, true);
			} else {
				delete st.warnings['notExpectedVisit'];
				if (!this.config.multiImportMode) {
					st.visit = expectedVisit;
				}
			}

			// Check if visit ID is set
			if (st.visit == null || typeof st.visit.idVisit === undefined) {
				st.setWarning('visitID', 'You should check/select the patient. Null visit ID.', false, true, false);
			} else {
				delete st.warnings['visitID'];
			}

			// Check inner series
			this.checkSeries(st);

			// Check if study has warnings
			if (st.hasWarnings()) {
				if (!st.hasCriticalWarnings() && st.hasValidSeries()) {
					this.setStatusStudy(st, 'incomplete');
				} else {
					this.setStatusStudy(st, 'rejected');
					this.dequeueStudy(st);
				}
			} else {
					this.setStatusStudy(st, 'valid');
			}

		}
	}

	// ~

	checkSeries(st) {
		function isset(e) {
			return !(e == 'null' || e === undefined || e == '');
		}

		for (let sr of st.series) {

			let dicomFile = sr.instances[0].dicomFile;

			// Check missing tags
			if (!isset(dicomFile.getModality())) {
				sr.setWarning('missingTag00080060', 'Missing tag: Modality', true);
			} else {
				if (!isset(dicomFile.getStudyDate())) {
					sr.setWarning('missingTag00080020', 'Missing tag: StudyDate', true);
				}
				if (sr.modality == 'PT') {
					if (!isset(dicomFile.get('00101030'))) {
						sr.setWarning('missingTag00101030', 'Missing tag: Patient Weight', true);
					}
					if (!isset(dicomFile.get('00080030'))) {
						sr.setWarning('missingTag00101030', 'Missing tag: Modality', true);
					}
					if (!isset(dicomFile.get('00181074'))) {
						sr.setWarning('missingTag00181074', 'Missing tag: Radionuclide Total Dose', true);
					}
					if (!isset(dicomFile.get('00181072'))) {
						sr.setWarning('missingTag00181072', 'Missing tag: Radiopharmaceutical Start Time', true);
					}
					if (!isset(dicomFile.get('00181075'))) {
						sr.setWarning('missingTag00181075', 'Missing tag: Radionuclide Half Life', true);
					}
					/*
					if (!isset(dicomFile.get('00181077'))) {
						sr.setWarning('missingTag00181077', 'Missing tag: RadiopharmaceuticalSpecificActivity', true);
					}*/
				}
			}

			// Check number of instances
			if(sr.getNbInstances() < this.config.minNbOfInstances) {
				sr.setWarning(`lessThan${this.config.minNbOfInstances}Instances`, `This serie contains less than ${this.config.minNbOfInstances} instances`, true, false);
			} else {
				delete sr.warnings[`lessThan${this.config.minNbOfInstances}Instances`];
			}

			if (sr.hasWarnings()) {
				st.setStatusSerie(sr, 'rejected');
				st.dequeueSerie(sr);
				st.setWarning('serie' + sr.seriesNumber, 'Invalid serie: #' + sr.seriesNumber + '.', false, false);
			} else {
				st.setStatusSerie(sr, 'valid');
				delete st.warnings['serie' + sr.seriesNumber];
			}
		}
	}

	// ~

	findExpectedVisit(st) {
		let thisP = st.getPatientName();

		if (thisP.givenName === undefined) {
			return undefined;
		}
		if (thisP.familyName === undefined) {
			return undefined;
		}

		thisP.birthDate = st.getPatientBirthDate();
		thisP.sex = st.patientSex;

		if (thisP.birthDate === undefined || thisP.sex === undefined) {
			return undefined;
		}

		// Linear search through expected visits list
		for (let visit of this.expectedVisits) {
			if (visit.firstName.trim().toUpperCase().charAt(0) == thisP.givenName.trim().toUpperCase().charAt(0)
				&& visit.lastName.trim().toUpperCase().charAt(0) == thisP.familyName.trim().toUpperCase().charAt(0)
				&& visit.sex.trim().toUpperCase().charAt(0) == thisP.sex.trim().toUpperCase().charAt(0)
				&& Util.isProbablyEqualDates(visit.birthDate, thisP.birthDate)
				) {
				return visit;
			}
		};
		return undefined;

	}
}