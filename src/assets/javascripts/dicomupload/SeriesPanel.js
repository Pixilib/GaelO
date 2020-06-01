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

class SeriesPanel {
	constructor() {

		this.dom = $('#du-series');

		this.study = null;
		this.selected = null;

		this.dom.append(`
			<span class="title">Series</span>

			<div class="row">
				<table class="table table-responsive col-sm-8">
					<thead>
						<tr>
							<th></th>
							<th>Status</th>
							<th>Description</th>
							<th>Modality</th>
							<th>#</th>
							<th>Date</th>
							<th>Nb of Instances</th>
						</tr>
					</thead>
					<tbody id="du-series-tbody">
					</tbody>
				</table>

				<table class="table table-responsive col-sm-4">
					<thead>
						<tr>
							<th>Warnings</th>
						</tr>
					</thead>
					<tbody id="du-series-warnings">
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

	update(study) {
		this.show();

		// Load previous study if param is not defined
		if (study === undefined) {
			study = this.study;
		}
		this.study = study;

		$('#du-series-tbody').empty();
		$('#du-series-warnings').empty();

		for (let sr of study.series) {

			let classes = [];
			let inputAttr = [];
			let status;

			switch (sr.status) {
				case 'valid':
					status = 'Valid';
					classes.push('row-valid');
					if (study.status == 'rejected') {
						inputAttr.push('disabled');
					} else if (sr.isQueued) {
						inputAttr.push('checked');
					}
					break;

				case 'rejected':
					status = 'Rejected';
					classes.push('row-danger');
					inputAttr.push('disabled');
					break;
			}

			this.insertRow(sr, {
				classes: classes,
				inputAttr: inputAttr,
				status: status
			});

			// Row onclick event
			$('#du-sr-' + Util.jq(sr.seriesInstanceUID)).on('click', () => {
				this.dom.find('tr').removeClass('row-clicked');
				this.dom.find('#du-sr-' + Util.jq(sr.seriesInstanceUID)).addClass('row-clicked');

				Util.dispatchEventOn('selectSerie', this.dom[0], {
					serie: sr
				});
			});

			// Checkbox onclick event
			const checkbox = $(`#du-sr-${Util.jq(sr.seriesInstanceUID)} input[name="${Util.jq(sr.seriesInstanceUID)}"]`);
			checkbox.on('click', () => {
				Util.dispatchEventOn('checkboxChange', this.dom[0], {
					study: study,
					serie: sr,
					isChecked: checkbox.is(':checked')
				});
			});

		}

		// Select the previously selected row
		if (this.selected !== null) {
			$('#du-sr-' + Util.jq(this.selected)).click();
		}
	}

	updateWarnings(sr) {
		$('#du-series-warnings').empty();

		let count = 0;

		for (let w in sr.warnings) {
			let rowNumber = count;
			if (sr.warnings[w].visible) {
				if (sr.warnings[w].ignore) {
					$('#du-series-warnings').append(`
					<tr class="ignored">
						<td>
							[ignored] ${sr.warnings[w].content}
						</td>
						<td>
						${(sr.warnings[w].ignorable) ? `<button id="du-series-warnings-${rowNumber}" class="">Consider</button>` : ''}
						</td>
					</tr>
				`);

					$(`#du-series-warnings-${rowNumber}`).on('click', () => {
						Util.dispatchEventOn('considerWarning', this.dom[0], {
							serie: sr,
							warningName: w
						});
					});

				} else {
					$('#du-series-warnings').append(`
					<tr>
						<td>
							${sr.warnings[w].content}
						</td>
						<td>
							${(sr.warnings[w].ignorable) ? `<button id="du-series-warnings-${rowNumber}" class="">Ignore</button>` : ''}
						</td>
					</tr>
				`);

					$(`#du-series-warnings-${rowNumber}`).on('click', () => {
						Util.dispatchEventOn('ignoreWarning', this.dom[0], {
							serie: sr,
							warningName: w
						});
					});
				}
				count++;
			}
		}
	}

	insertRow(serie, params) {
		let args = {
			classes: [],
			inputAttr: [],
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

		$('#du-series-tbody').append(`
		<tr id="du-sr-${µ(serie.seriesInstanceUID)}" class="row-clickable ${classes}">
			<td><input name="${µ(serie.seriesInstanceUID)}" type="checkbox" ${inputAttr}></td>
			<td>${args.status}</td>
			<td>${Util.ft(serie.seriesDescription)}</td>
			<td>${Util.ft(serie.modality)}</td>
			<td>${Util.ft(serie.seriesNumber)}</td>
			<td>${Util.ft(serie.getDate('seriesDate'))}</td>
			<td>${Util.ft(serie.getNbInstances())}</td>
		</tr>
	`);

	}

}