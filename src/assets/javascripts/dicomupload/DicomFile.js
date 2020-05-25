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

class DicomFile {
	constructor(originalFile, dataSet) {
		this.originalFile = originalFile;
		this.dataSet = dataSet;
		this.header = this.retrieveHeaderData(dataSet.byteArray);
		this.removeByteArrayReferences();
	}

	removeByteArrayReferences(dataSet = this.dataSet) {
		// Recursively delete byteArray references
		for (let propName in dataSet) {
			let prop = dataSet[propName];

			// Check inner elements
			if (propName == 'elements') {
				for (let elmt in prop) {
					if (prop[elmt].items !== undefined) {
						for (let it of prop[elmt].items) {
							this.removeByteArrayReferences(it.dataSet);
						}
					}
				}
			}

			if (propName == 'byteArray' || propName == 'byteArrayParser') {
				// Delete reference to the object
				dataSet[propName] = null;
			}

		}
	}

	retrieveHeaderData(byteArray) {
		let pxData = this.dataSet.elements.x7fe00010;
		//If no pixel data return the full byte array
		if(pxData === undefined){
			return byteArray.slice()
		}
		//if pixel data here return only header
		return byteArray.slice(0, pxData.dataOffset-1);
	}

	anonymise(tagsToErase) {
		if (tagsToErase === undefined) {
			tagsToErase = [
				'00101005',	// Patient's Birth Name
				'00100010', // Patient's Name
				'00100020', // Patient's ID
				'00100030',	// Patient's Birth Date
				'00101040', // Patient's Address
				'00080050',	// Accession Number
				'00080080',	// Institution Name
				'00080081',	// Institution Adress
				'00080090',	// Referring Physician's Name
				'00080092',	// Referring Physician's Adress
				'00080094', // Refering Physician's Telephone Number
				'00080096', // Referring Pysician ID Sequence
				'00081040', // Institutional Departement Name
				'00081048', // Physician Of Record
				'00081049', // Physician Of Record ID Sequence
				'00081050', // Performing Physician's Name
				'00081052', // Performing Physicians ID Sequence
				'00081060', // Name Of Physician Reading Study
				'00081062', // Physician Reading Study ID Sequence
				'00081070', // Operators Name
				'00200010', // Study ID
				'0040A123'  // Person Name
			];
		}

		let notFoundTags = [];

		for (let id of tagsToErase) {
			try {
				this.erase(id);
			} catch (e) {
				// Only catch "Can't find tag id" error
				if (e != `Can't find ${id} while erasing.`) {
					throw e;
				}
				notFoundTags.push(id);
			}
		}

		/*console.warn(`Couldn't find ${notFoundTags.toString()}`
			+ ` while anonymising ${this.originalFile.name}`
			+ ` => These tags will be skipped.`);*/
	}

	/**
	 * Write unsignificant content at a specified tag in the dataset
	 */
	erase(id, newContent = '*') {
		id = id.toLowerCase();

		const element = this.dataSet.elements[`x${id}`];

		if (element === undefined) {
			throw `Can't find ${id.toUpperCase()} while erasing.`;
		}

		// Retrieve the index position of the element in the data set array
		const dataOffset = element.dataOffset;

		// Retrieve the length of the element
		const length = element.length;

		// Fill the field with unsignificant values
		for (let i = 0; i < length; i++) {
			// Get charcode of the current char in 'newContent'
			const char = newContent.charCodeAt(i % newContent.length);

			// Write this char in the array
			this.header[dataOffset + i] = char;
		}
	}

	getRadiopharmaceuticalTag(tagAddress){
		try{
			let elmt = this.dataSet.elements['x00540016']
			let radioPharmElements = elmt.items[0].dataSet.elements
			return this.string(radioPharmElements['x'+tagAddress])
		}catch ( error ) { 
			console.log(error)
			return undefined 
		}
	}

	getDicomTag(tagAddress){
		let elmt = this.dataSet.elements['x'+tagAddress]
		if ( elmt !== undefined && elmt.length > 0) {
			// Return the value of the dicom attribute
			return this.string(elmt);
		}
		else return undefined
	}

	getAccessionNumber() {
		return this.getDicomTag("00080050");
	}
	getAcquisitionDate() {
		return this.getDicomTag("00080020");
	}
	getInstanceNumber() {
		return this.getDicomTag("00200013");
	}
	getModality() {
		return this.getDicomTag("00080060");
	}
	getPatientBirthDate() {
		return this.getDicomTag("00100030");
	}
	getPatientID() {
		return this.getDicomTag("00100020");
	}
	getPatientName() {
		return this.getDicomTag("00100010");
	}
	getPatientSex() {
		return this.getDicomTag("00100040");
	}
	getSeriesInstanceUID() {
		return this.getDicomTag("0020000e");
	}
	getSeriesDate() {
		return this.getDicomTag("00080021");
	}
	getSeriesDescription() {
		return this.getDicomTag("0008103e");
	}
	getSOPInstanceUID() {
		return this.getDicomTag("00080018");
	}
	getSOPClassUID() {
		return this.getDicomTag("00080016");
	}
	//SK A TESTER : On ne pourrait utiliser que le 0002,0002
	//Ce tag est un duplicat de 00080016 cf https://stackoverflow.com/questions/32689446/is-it-true-that-dicom-media-storage-sop-instance-uid-sop-instance-uid-why
	getMediaStorageSOP(){
		return this.getDicomTag("00020002")
	}
	getSeriesNumber() {
		return this.getDicomTag('00200011')
	}
	getStudyInstanceUID() {
		return this.getDicomTag('0020000d')
	}
	getStudyDate() {
		return this.getDicomTag("00080020");
	}
	getStudyID() {
		return this.getDicomTag("00200010");
	}
	getStudyDescription() {
		return this.getDicomTag("00081030");
	}

	/**
	 * Returns element contain as a string
	 * @param {*} element element from the data set
	 */
	string(element) {
		let position = element.dataOffset;
		let length = element.length;

		if (length < 0) {
			throw 'Negative length';
		}
		if (position + length > this.header.length) {
			throw 'Out of range index';
		}

		var result = '';
		var byte;

		for (var i = 0; i < length; i++) {
			byte = this.header[position + i];
			if (byte === 0) {
				position += length;
				return result.trim();
			}
			result += String.fromCharCode(byte);
		}
		return result.trim();
	}

	isSecondaryCaptureImg() {
		const secondaryCaptureImgValues = [
			'1.2.840.10008.5.1.4.1.1.7',
			'1.2.840.10008.5.1.4.1.1.7.1',
			'1.2.840.10008.5.1.4.1.1.7.2',
			'1.2.840.10008.5.1.4.1.1.7.3',
			'1.2.840.10008.5.1.4.1.1.7.4',
			'1.2.840.10008.5.1.4.1.1.88.11',
			'1.2.840.10008.5.1.4.1.1.88.22',
			'1.2.840.10008.5.1.4.1.1.88.33',
			'1.2.840.10008.5.1.4.1.1.88.40',
			'1.2.840.10008.5.1.4.1.1.88.50',
			'1.2.840.10008.5.1.4.1.1.88.59',
			'1.2.840.10008.5.1.4.1.1.88.65',
			'1.2.840.10008.5.1.4.1.1.88.67'
		];
		return secondaryCaptureImgValues.includes(this.getSOPClassUID());
	}

	isDicomDir(){
		const dicomDirSopValues = [
			'1.2.840.10008.1.3.10'
		]
		return dicomDirSopValues.includes(this.getMediaStorageSOP());
	}

	clearData() {
		this.header = null;
		this.dataSet = null;
	}
}