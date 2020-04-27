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

class CheckPatientPanel {
	constructor() {
		this.init();
	}

	reset() {
		this.init();
		this.checkTable();
	}

	init() {
		this.expectedVisits = [];
		this.study = undefined;

		this.dom = $('#du-patient');

		// ~ 

		this.fields = {};

		function createField(name) {
			let field = {
				idRow: 'du-patp-' + name,
				idExpected: 'du-patp-expct-' + name,
				idCurrent: 'du-patp-crrnt-' + name,
				idBtn: 'du-patp-btn-' + name,
				ignore: false,

				set: function (expct, curr) {
					this.setExpected(expct);
					this.setCurrent(curr);
				},

				setExpected: function (expct) {
					$('#' + this.idExpected).text(expct);
				},

				setCurrent: function (curr) {
					$('#' + this.idCurrent).text(curr);
				},

				match: function () {
					let expct = $('#' + this.idExpected).text().toUpperCase();
					let currt = $('#' + this.idCurrent).text().toUpperCase();
					// Try to compare as text string
					if (expct === currt) {
						return true;
					}
					// Try to compare as two dates
					if (Util.isProbablyEqualDates(expct, currt)) {
						return true;
					}
					return false;
				},

				update: function () {
					$('#' + this.idRow).addClass('row-success');
					$('#' + this.idRow).removeClass('row-danger');
				}
			};

			return field;
		}

		this.fields.fname = createField('fname');
		this.fields.lname = createField('lname');
		this.fields.birthd = createField('birthd');
		this.fields.sex = createField('sex');
		this.fields.acqd = createField('acqd');
	}

	update(study, expectedVisits) {
		this.study = study;
		this.expectedVisits = expectedVisits;
		this.updateHTML();
		this.fillTable();
		this.checkTable();
	}

	updateHTML() {
		this.dom.html(`
				<div class="modal-dialog modal-dialog-centered" role="document">
					<div class="modal-content">
						<div class="modal-header">
							<h5 class="modal-title" id="du-patientLongTitle">Check Patient</h5>
							<button type="button" class="close" data-dismiss="modal" aria-label="Close">
								<span aria-hidden="true">&times;</span>
							</button>
						</div>
						<div class="modal-body">
							<div id="du-patp-comparison">
								<p>The imported patient informations do not match with the ones in the server. We let you check these informations below:</p>
								<table class="table table-sm">
									<thead>
										<tr>
											<th></th>
											<th>Expected</th>
											<th>Current</th>
											<th></th>
										</tr>
									</thead>
									<tbody>
										<tr id="${this.fields.fname.idRow}">
											<th>First name</th>
											<td id="${this.fields.fname.idExpected}"></td>
											<td id="${this.fields.fname.idCurrent}"></td>
											<td><button id="${this.fields.fname.idBtn}">Ignore</button></td>
										</tr>
										<tr id="${this.fields.lname.idRow}">
											<th>Last name</th>
											<td id="${this.fields.lname.idExpected}"></td>
											<td id="${this.fields.lname.idCurrent}"></td>
											<td><button id="${this.fields.lname.idBtn}">Ignore</button></td>
										</tr>
										<tr id="${this.fields.birthd.idRow}">
											<th>Birth date</th>
											<td id="${this.fields.birthd.idExpected}"></td>
											<td id="${this.fields.birthd.idCurrent}"></td>
											<td><button id="${this.fields.birthd.idBtn}">Ignore</button></td>
										</tr>
										<tr id="${this.fields.sex.idRow}">
											<th>Sex</th>
											<td id="${this.fields.sex.idExpected}"></td>
											<td id="${this.fields.sex.idCurrent}"></td>
											<td><button id="${this.fields.sex.idBtn}">Ignore</button></td>
										</tr>
										<tr id="${this.fields.acqd.idRow}">
											<th>Acquisition date</th>
											<td id="${this.fields.acqd.idExpected}"></td>
											<td id="${this.fields.acqd.idCurrent}"></td>
											<td><button id="${this.fields.acqd.idBtn}">Ignore</button></td>
										</tr>
									</tbody>
								</table>
							</div>
							<p>If you want to force the upload you may have to ignore all the warnings.</p>
						</div>
						<div class="modal-footer">
							<button id="du-patp-btn-cancel" type="button" class="btn btn-secondary mr-3" data-dismiss="modal">Cancel</button>
							<button id="du-patp-btn-confirm" type="button" class="btn btn-primary" data-dismiss="modal">This is the correct patient</button>
						</div>
					</div>
				</div>
		`);


		for (let f in this.fields) {
			f = this.fields[f];
			let idBtn = f.idBtn;

			$('#' + idBtn).on('click', () => {
				if (f.ignore) {
					$('#' + idBtn).text('Ignore');
					f.ignore = false;
				} else {
					$('#' + idBtn).text('Consider');
					f.ignore = true;
				}
				this.checkTable();
			});
		}


		$('#du-patp-btn-confirm').on('click', () => {
			Util.dispatchEventOn('confirm', this.dom[0], {
				study: this.study,
				hasWarnings: this.hasWarnings()
			});
		});
	}

	fillTable() {
		if (this.expectedVisits.length !== 1) {
			throw Error('Invalid expected visits list length. Expected 1, but had ' + this.expectedVisits.length);
		}
		const v = this.expectedVisits[0];
		const st = this.study;

		this.fields.fname.setExpected(v.firstName);
		this.fields.lname.setExpected(v.lastName);
		this.fields.birthd.setExpected(Util.fDate(v.birthDate));
		this.fields.sex.setExpected(v.sex);
		this.fields.acqd.setExpected(Util.fDate(v.acquisitionDate));

		let patientName = st.getPatientName();

		if(patientName != undefined){
			if (patientName.givenName != undefined) {
				this.fields.fname.setCurrent(patientName.givenName.charAt(0));
			}
			if (patientName.familyName != undefined) {
				this.fields.lname.setCurrent(patientName.familyName.charAt(0));
			}
		}

		this.fields.birthd.setCurrent(Util.fDate(st.getPatientBirthDate()));

		this.fields.sex.setCurrent(st.patientSex);

		this.fields.acqd.setCurrent(Util.fDate(st.getAcquisitionDate()));
	}

	checkTable() {
		for (let f in this.fields) {
			f = this.fields[f];
			if (f.match()) {
				$('#' + f.idRow).addClass('row-success');
				$('#' + f.idRow).removeClass('row-danger');
				$('#' + f.idBtn).attr('hidden', '');
			} else if (f.ignore) {
				$('#' + f.idRow).removeClass('row-success');
				$('#' + f.idRow).removeClass('row-danger');
				$('#' + f.idBtn).removeAttr('hidden');
			} else {
				$('#' + f.idRow).addClass('row-danger');
				$('#' + f.idRow).removeClass('row-success');
				$('#' + f.idBtn).removeAttr('hidden');
			}
		}

		if (this.hasWarnings()) {
			$('#du-patp-btn-confirm').attr('disabled', '');
		} else {
			$('#du-patp-btn-confirm').removeAttr('disabled');
		}
	}

	hasWarnings() {
		for (let f in this.fields) {
			f = this.fields[f];
			if (!f.match() && !f.ignore) {
				return true;
			}
		}
		return false;
	}
}