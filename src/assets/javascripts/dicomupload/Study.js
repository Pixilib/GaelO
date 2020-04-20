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

class Study {
	constructor(stiuid, stdate, stdesc, stid, an, acqd, pid, pn, pbd, ps) {
		this.studyInstanceUID = stiuid;
		this.studyDate = stdate;
		this.studyDescription = stdesc;
		this.studyID = stid;
		this.accessionNumber = an;
		this.acquisitionDate = acqd;
		this.patientID = pid;
		this.patientName = pn;
		this.patientBirthDate = pbd;
		this.patientSex = ps;
		this.series = [];
		this.validSeries = [];     // have passed the checks
		this.rejectedSeries = [];  // do not have passed the checks
		this.queuedSeries = [];    // in wait for uplaod
		this.visit = null;
		this.warnings = {};
		this.isQueued = undefined;
		this.isUploadAborted = undefined;
	}

	getDate(attribute) {
		try {
			let date = dicomParser.parseDA(this[attribute]);

			date.toString = () => {
				return Util.fDate(date);
			}
			return date;
		} catch (e) {
			return undefined;
		}
	}

	getAcquisitionDate() {
		return this.getDate('acquisitionDate');
	}

	getPatientName() {
		try {
			let name = dicomParser.parsePN(this.patientName);
			name.toString = () => {
				let fullname = name.familyName + ' ' + name.givenName;
				return fullname.replace('undefined', '').trim();
			}
			return name;
		} catch (e) {
			return undefined;
		}
	}

	getPatientBirthDate() {
		return this.getDate('patientBirthDate');
	}

	getNbQueuedInstances() {
		let res = 0;
		for (let sr of this.queuedSeries) {
			res += sr.instances.length;
		}
		return res;
	}

	getOrthancID() {
		const pID = this.patientID;
		const stiuid = this.studyInstanceUID;
		let hash = CryptoJS.SHA1(pID + '|' + stiuid).toString();
		return `${hash.substring(0, 8)}-${hash.substring(8, 16)}-${hash.substring(16, 24)}-${hash.substring(24, 32)}-${hash.substring(32, 40)}`;
	}

	setStatusSerie(serie, status) {
		serie.status = status;
		this.remove(serie, 'rejectedSeries');
		this.remove(serie, 'validSeries');
		switch (status) {
			case 'rejected':
				this.put(serie, 'rejectedSeries');
				break;
			case 'valid':
				this.put(serie, 'validSeries');
				break;
			default:
				//console.warn('Invalid Serie State');
		}
	}


	hasWarnings() {
		for (let w in this.warnings) {
			if (!this.warnings[w].ignore) {
				return true;
			}
		}
		return false;
	}

	hasWarning(property) {
		for (let w in this.warnings) {
			if (w == property && !this.warnings[w].ignore) {
				return true;
			}
		}
		return false;
	}

	hasOnlyWarning(property) {
		let res = false;
		for (let w in this.warnings) {
			if (w == property) {
				res = !this.warnings[w].ignore;
			} else {
				if (this.warnings[w].ignore === false) {
					return false;
				}
			}
		}
		return res;
	}

	hasCriticalWarnings() {
		for (let w in this.warnings) {
			if (this.warnings[w].critical && !this.warnings[w].ignore) {
				return true;
			}
		}
		return false;
	}

	setWarning(name, content, ignorable = false, critical = true, visible = true) {
		if (this.warnings[name] === undefined) {
			this.warnings[name] = {
				content: content,
				ignore: false,
				ignorable: ignorable,
				critical: critical,
				visible: visible
			};
		} else {
			this.warnings[name].content = content;
		}
	}

	ignoreWarning(name) {
		this.warnings[name].ignore = true;
	}

	considerWarning(name) {
		this.warnings[name].ignore = false;
	}

	/**
   * Delete specific element from a given array
   */
	remove(elmt, arrName) {
		const index = this[arrName].indexOf(elmt);
		if (index > -1) {
			this[arrName].splice(index, 1);
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

	queueSerie(serie) {
		serie.isQueued = true;
		this.put(serie, 'queuedSeries');
	}

	dequeueSerie(serie) {
		serie.isQueued = false;
		this.remove(serie, 'queuedSeries');
	}

	dequeueAllSeries() {
		while (this.queuedSeries.length > 0) {
			this.dequeueSerie(this.queuedSeries[0]);
		}
	}

	hasQueuedSeries() {
		return this.queuedSeries.length > 0;
	}

	hasValidSeries() {
		return this.validSeries.length > 0;
	}

	hasAllSeriesQueued() {
		return this.series.length === this.queuedSeries.length;
	}

	clearNonQueuedSeries() {
		for (let sr of this.series) {
			if (!sr.isQueued)
				sr.clearAllInstances();
		}
	}

	clearAllSeries() {
		for (let sr of this.series) {
			sr.clearAllInstances();
		}
	}
}