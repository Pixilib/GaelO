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
	* 
	*/
class DAO {
	static fetchExpectedVisits(config, callback) {
		$.ajax({
			global: false, // Do not dispatch event 'ajaxSend' to avoid ajax aborting
			type: "GET",
			url: config.expectedVisitsURL,
			success: function (r) {
				let data;
				try {
					data = JSON.parse(r);
				} catch (e) {
					throw Error('Could not read the expected visits list.');
				}
		
				// Declare formatted JSON response variable
				let fResp = [];
		
				for (let visitName in data.AvailablePatients) {
					let visit = data.AvailablePatients[visitName];
					for (let pat of visit) {
						let fRespPat = {};
		
						// Add property that indicates if this visit has been selected by the user
						fRespPat.isSelected = false;
		
						// Hydrating 'fRespPat'
						fRespPat.visitName = visitName;
		
						fRespPat.patientCode = pat.numeroPatient;
		
						fRespPat.firstName = pat.firstName;
						fRespPat.lastName = pat.lastName;
		
						fRespPat.birthDate = {};
						fRespPat.birthDate.month = pat.patientDOB.toString().trim().toUpperCase().split("-")[0];
						fRespPat.birthDate.day = pat.patientDOB.toString().trim().toUpperCase().split("-")[1];
						fRespPat.birthDate.year = pat.patientDOB.toString().trim().toUpperCase().split("-")[2];
		
						fRespPat.sex = pat.patientSex.toString().charAt(0).toUpperCase();
						fRespPat.acquisitionDate = {};
						fRespPat.acquisitionDate.month = pat.acquisitionDate.toString().trim().toUpperCase().split("-")[0];
						fRespPat.acquisitionDate.day = pat.acquisitionDate.toString().trim().toUpperCase().split("-")[1];
						fRespPat.acquisitionDate.year = pat.acquisitionDate.toString().trim().toUpperCase().split("-")[2];
		
						fRespPat.idVisit = pat.idVisit;
		
						if (config.multiImportMode) {
							// We are in multiple import mode
							// Get all the expected visit of the investigator
							fResp.push(fRespPat);
						} else {
							// We are in single import mode
							// Only get the expected visit with the specified id
							if (fRespPat.idVisit == config.idVisit) {
								fResp.push(fRespPat);
							}
						}
					}
				}
		
				if (fResp.length === 0) {
					console.warn('Dicom Upload Js: Expected Visits List is empty.');
				}
				callback(fResp);
			}
		});
	}

	static fetchIsNewStudy(config, orthancID, callback) {
		$.ajax({
			global: false, // Do not dispatch event 'ajaxSend' to avoid ajax aborting
			type: "POST",
			url: config.isNewStudyURL,
			data: {
				'originalOrthancID': orthancID
			},
			success: function (r) {
				callback(r);
			}
		});
	}
	

	/**
	 * Send the needed study information for the server-sided validation
	 */
	static requestConfirmation(config, fileName, visitID, studyInstUID, nbInstances, orthancID) {
		$.ajax({
			global: false, // Do not dispatch event 'ajaxSend' to avoid ajax aborting
			type: "POST",
			dataType: "json",
			url: config.validationScriptURL,
			data: {
				'file_name': fileName,
				'id_visit': visitID,
				'study_instance_uid': studyInstUID,
				'nb_instances': nbInstances,
				'anonFromId': orthancID
			}
		});
	}
}