/**
 Copyright (C) 2018 KANOUN Salim
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

class Serie {
	constructor(sriuid, srn, srd, srdesc, modlty) {
		this.seriesInstanceUID = sriuid;
		this.seriesNumber = srn;
		this.seriesDate = srd;
		this.seriesDescription = srdesc;
		this.modality = modlty;
		this.instances = [];
		this.warnings = {};
	}

	getDate(property) {
		try {
			let date = dicomParser.parseDA(this[property]);

			function intToString(integer, digits) {
				while (integer.toString().length < digits) {
					integer = '0' + integer;
				}
				return integer;
			}

			date.toString = () => {
				return date.year + '-' + intToString(date.month, 2) + '-' + intToString(date.day, 2);
			}
			return date;
		} catch (e) {
			return undefined;
		}
	}

	getNbInstances() {
		return this.instances.length;
	}

	hasWarnings() {
		let nbConsideredWarnings = 0;
		for (let w in this.warnings) {
			if (!this.warnings[w].ignore) {
				nbConsideredWarnings++;
			}
		}
		return nbConsideredWarnings > 0;
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

	clearAllInstances() {
		for (let inst of this.instances) {
			inst.dicomFile.clearData();
		}
	}
}