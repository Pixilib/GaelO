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

class StudiesPanel {
	constructor(config) {
		this.config = config;

		this.dom = $('#du-studies');

		this.selected = null;

		this.dom.append(`
			<span class="title">Studies</span>
			<div class="row">
				<table class="table table-responsive col-sm-8">
					<thead>
						<tr>
							<th></th>
							<th></th>
							<th></th>
							<th>Status</th>
							<th>Patient name</th>
							<th>Description</th>
							<th>Accession #</th>
							<th>Date</th>
						</tr>
					</thead>
					<tbody id="du-studies-tbody">
					</tbody>
				</table>

				<table class="table table-responsive col-sm-4">
					<thead>
						<tr>
							<th>Warnings</th>
						</tr>
					</thead>
					<tbody id="du-studies-warnings">
					</tbody>
				</table>
			</div>
		`);

		this.hide();
	}

	hide() {
		this.dom.attr('hidden', '');
	}

	show() {
		this.dom.removeAttr('hidden');
	}

	update(studies) {
		if (studies.length > 0) {
			this.show();
		} else {
			this.hide();
		}
		$('#du-studies-tbody').empty();

		for (let st of studies) {

			let classes = [];
			let inputAttr = [];
			let checkBoxIsIndeterminated = false;
			let patp;
			let patRemove;
			let status;

			switch (st.status) {
				case 'valid':
					status = 'Valid';
					classes.push('row-valid');
					if (st.isQueued) {
						if (st.hasAllSeriesQueued()) {
							inputAttr.push('checked');
						} else {
							checkBoxIsIndeterminated = true;
						}
					}
					break;

				case 'incomplete':
					status = 'Incomplete';
					classes.push('row-warning');
					if (st.isQueued) {
						if (st.hasAllSeriesQueued()) {
							inputAttr.push('checked');
						} else {
							checkBoxIsIndeterminated = true;
						}
					}
					break;

				case 'rejected':
					status = 'Rejected';
					classes.push('row-danger');
					inputAttr.push('disabled');
					break;
			}

			if (this.config.multiImportMode) {
				if (st.hasWarning('visitID')) {
					patp = `<button id="du-patp-btn-${st.studyInstanceUID}" type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#du-patient">Select Patient</button>`;
					inputAttr.push('disabled');
				} else {
					patp = st.visit.patientCode;
					patRemove = `<button id="du-pat-remove-btn-${st.studyInstanceUID}" class="btn btn-sm btn-danger du-pat-remove-btn" title="Remove patient">&times;</button>`;
				}
			} else {
				if (st.hasWarning('notExpectedVisit')) {
					patp = `<button id="du-patp-btn-${st.studyInstanceUID}" type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#du-patient">Check Patient</button>`;
					inputAttr.push('disabled');
				}
			}

			this.insertRow(st, {
				classes: classes,
				inputAttr: inputAttr,
				patientPanel: patp,
				patientRemove: patRemove,
				status: status
			});


			// Set indeterminate checkbox if not all series are selected
			if (checkBoxIsIndeterminated) {
				$(`#du-st-${Util.jq(st.studyInstanceUID)} input[name="${Util.jq(st.studyInstanceUID)}"]`).prop('indeterminate', true);
			}

			// 'Select/Check Patient' button 'on click' event
			$(`#du-patp-btn-${Util.jq(st.studyInstanceUID)}`).on('click', () => {
				Util.dispatchEventOn('patpClick', this.dom[0], {
					study: st
				});
			});

			// 'Remove Patient' button 'on click' event
			$(`#du-pat-remove-btn-${Util.jq(st.studyInstanceUID)}`).on('click', () => {
				Util.dispatchEventOn('patRemoveClick', this.dom[0], {
					study: st
				});
			});

			// Row 'on click' event
			$('#du-st-' + Util.jq(st.studyInstanceUID)).on('click', () => {
				this.dom.find('tr').removeClass('row-clicked');
				this.dom.find('#du-st-' + Util.jq(st.studyInstanceUID)).addClass('row-clicked');

				Util.dispatchEventOn('selectStudy', this.dom[0], {
					study: st
				});
			});

			// Checkbox 'on click' event
			const checkbox = $(`#du-st-${Util.jq(st.studyInstanceUID)} input[name="${Util.jq(st.studyInstanceUID)}"]`);
			checkbox.on('click', () => {
				Util.dispatchEventOn('checkboxChange', this.dom[0], {
					study: st,
					isChecked: checkbox.is(':checked')
				});
			});
		}

		// Select the previously selected row
		if (this.selected !== null) {
			$('#du-st-' + Util.jq(this.selected)).click();
		}
	}


	updateWarnings(st) {
		$('#du-studies-warnings').empty();
		$('#du-series-warnings').empty();

		let count = 0;

		for (let w in st.warnings) {
			let rowNumber = count;
			if (st.warnings[w].visible) {
				if (st.warnings[w].ignore) {
					$('#du-studies-warnings').append(`
						<tr class="ignored">
							<td>
								[ignored] ${st.warnings[w].content}
							</td>
							<td>
							${(st.warnings[w].ignorable) ? `<button id="du-studies-warnings-${rowNumber}" class="">Consider</button>` : ''}
							</td>
						</tr>
					`);

					$(`#du-studies-warnings-${rowNumber}`).on('click', () => {
						Util.dispatchEventOn('considerWarning', this.dom[0], {
							study: st,
							warningName: w
						});
					});

				} else {
					$('#du-studies-warnings').append(`
						<tr>
							<td>
								${st.warnings[w].content}
							</td>
							<td>
							${(st.warnings[w].ignorable) ? `<button id="du-studies-warnings-${rowNumber}" class="">Ignore</button>` : ''}
							</td>
						</tr>
					`);

					$(`#du-studies-warnings-${rowNumber}`).on('click', () => {
						Util.dispatchEventOn('ignoreWarning', this.dom[0], {
							study: st,
							warningName: w
						});
					});
				}
				count++;
			}
		}
	}

	insertRow(study, params) {
		let args = {
			classes: [],
			inputAttr: [],
			patientPanel: '',
			patientRemove: '',
			status: ''
		}

		if (params !== undefined) {
			// Overrides default args with params
			for (let e in args) {
				if (params[e] !== undefined) {
					args[e] = params[e];
				}
			}
		}

		let classes = Util.arrayToString(args.classes, ' ');
		let inputAttr = Util.arrayToString(args.inputAttr, ' ');

		$('#du-studies-tbody').append(`
			<tr id="du-st-${µ(study.studyInstanceUID)}" class="row-clickable ${classes}">
				<td><input name="${µ(study.studyInstanceUID)}" type="checkbox" ${inputAttr}></td>
				<td>${args.patientPanel}</td>
				<td>${args.patientRemove}</td>
				<td>${args.status}</td>
				<td>${Util.ft(study.getPatientName())}</td>
				<td>${Util.ft(study.studyDescription)}</td>
				<td>${Util.ft(study.accessionNumber)}</td>
				<td>${Util.ft(study.getDate('studyDate'))}</td>
			</tr>
		`);

	}

}