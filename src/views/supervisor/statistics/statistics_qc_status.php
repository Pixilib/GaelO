<?php
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
?>

<h1>Quality Control Status</h1>
<div id="chart-controls">

	<div class="form-group row">
		<label class="col-sm-2 col-form-label w-100">Group by</label>
		<div class="form-check form-check-inline">
			<input id="chart-controls-group-all" class="form-check-input" name="group" type="radio" value="all" checked>
			<label for="chart-controls-group-all" class="form-check-label">All</label>
		</div>
		<div class="form-check form-check-inline">
			<input id="chart-controls-group-center" class="form-check-input" name="group" type="radio" value="center">
			<label for="chart-controls-group-center" class="form-check-label">Center</label>
		</div>
		<div class="form-check form-check-inline">
			<input id="chart-controls-group-country" class="form-check-input" name="group" type="radio" value="country">
			<label for="chart-controls-group-country" class="form-check-label">Country</label>
		</div>
	</div>

	<div id="form-group-select-center" class="form-group row" hidden>
		<label class="col-sm-2 col-form-label w-100">Centers</label>
		<div class="col-sm-10">
			<select name="center" id="chart-controls-select-center" multiple></select>
			<button class="btn btn-sm btn-dark" onclick="select.center.selectAll()">Select All</button>
			<button class="btn btn-sm btn-secondary" onclick="select.center.deselectAll()">Deselect All</button>
		</div>
	</div>

	<div id="form-group-select-country" class="form-group row select" hidden>
		<label class="col-sm-2 col-form-label w-100">Countries</label>
		<div class="col-sm-10">
			<select name="country" id="chart-controls-select-country" multiple></select>
			<button class="btn btn-sm btn-dark" onclick="select.country.selectAll()">Select All</button>
			<button class="btn btn-sm btn-secondary" onclick="select.country.deselectAll()">Deselect All</button>
		</div>
	</div>

	<div id="form-group-display" class="form-group row" hidden>
		<label class="col-sm-2 col-form-label w-100">Display</label>
		<div class="form-check form-check-inline">
			<input id="chart-controls-number-qc-as-thickness" class="form-check-input" type="checkbox" checked>
			<label for="chart-controls-number-qc-as-thickness" class="form-check-label">Number of QC as thickness</label>
		</div>
	</div>

</div>

<div>
	<canvas id="chart-status"></canvas>
</div>

<div style="margin-top: 32px">
	<canvas id="chart-qc-with-ca"></canvas>
</div>



<script>
	var rawData = <?= $chartData ?>;

	var LABELS_STATUS = ['Accepted', 'Refused', 'Wait Definitive Conclusion', 'Corrective Action Asked', 'Not Done'];
	var COLORS_BG_STATUS = ['#9BEB48', '#D13030', '#7A56FF', '#FFBF48', '#FF5538'];
	//var COLORS_TEXT = ['#38541A', '#3D0E0E', '#171030', '#473615', '#632216'];

	var chartStatusObj = new Chart('chart-status', {
		type: 'pie',
		data: {
			labels: LABELS_STATUS,
			datasets: []
		},
		options: {
			plugins: {
				datalabels: false
			},
			title: {
				text: 'Quality Control status repartition',
				display: true
			},
			legend: {
				position: 'right'
			},
			cutoutPercentage: 10,
			tooltips: {
				callbacks: {
					// Custom label
					label: function(item, data) {
						let dataset = data.datasets[item.datasetIndex];
						let currentValue = dataset.data[item.index];

						let sum = 0;
						for (let value of dataset.data) {
							sum += value;
						}

						let percentage = ((currentValue / sum) * 100).toFixed(1);
						return [
							dataset.name,
							data.labels[item.index],
							`${currentValue} (${percentage} %)`
						];
					}
				}
			}
		}
	});

	// Init select inputs
	var select = {
		center: GaelOChart.createSelect(
			$('#chart-controls-select-center'),
			rawData,
			'center', {
				xGroup: 'country'
			}
		),
		country: GaelOChart.createSelect(
			$('#chart-controls-select-country'),
			rawData,
			'country'
		)
	}

	$(document).ready(() => {
		updateStatusData();
	});


	/**
	 * Performs data manipulation, removes useless data 
	 * and groups them by center or country
	 */
	function formatStatusData(rawData) {
		let group = $('input[name="group"]:checked').val();
		switch (group) {
			default:
				throw `Unknown group value.`;

			case 'all':
				var datasetData = [];
				for (let l of LABELS_STATUS) {
					let queryResult = alasql(`SELECT qcStatus, count(*) AS nb FROM ? WHERE qcStatus = "${l}" GROUP BY qcStatus ORDER BY qcStatus`, [rawData]);
					datasetData.push(queryResult[0].nb);
				}
				var datasets = [{
					name: 'All',
					data: datasetData,
					backgroundColor: COLORS_BG_STATUS
				}];

				return {
					datasets: datasets
				};

			case 'center':
			case 'country':
				var rawDatasets = getDatasetsQCStatus(group, rawData);
				var datasets = [];
				for (let grp in rawDatasets) {
					if ($('#chart-controls-number-qc-as-thickness').is(':checked')) {
						var weight = rawDatasets[grp].reduce((a, b) => a + b, 0);
					} else {
						var weight = 1;
					}
					datasets.push({
						name: grp,
						data: rawDatasets[grp],
						backgroundColor: COLORS_BG_STATUS,
						weight: weight
					});
				}
				return {
					datasets: datasets
				};
		}
	};


	/**
	 * Update the chart datasets with formated data
	 */
	function updateStatusData() {
		let formatedData = formatStatusData(rawData);
		chartStatusObj.data.datasets = formatedData.datasets;
		chartStatusObj.update();
	};


	/**
	 * Returns an object where each property is a 'group' (center or country)
	 * containing an array of the corresponding number of occurences of the qcStatus
	 * @param {string} group property name of the group values
	 */
	function getDatasetsQCStatus(group, data) {
		var qcs = alasql(`SELECT ${group}, qcStatus , COUNT(*) AS nb FROM ? GROUP BY ${group}, qcStatus ORDER BY ${group}, qcStatus`, [data]);
		return getDatasets(qcs, 'qcStatus', 'nb', LABELS_STATUS, group);
	}


	/**
	 * Returns object where each property is a 'group'
	 * containing an array of the corresponding 'data'
	 * @param {object} data alasql formated set of data
	 * @param {string} x property name of the x values
	 * @param {string} y property name of the y values
	 * @param {array} labels list of all x possible values
	 * @param {string} group property name of the group values
	 */
	function getDatasets(data, x, y, labels, group) {
		var res = {};

		for (let d of data) {
			if (!res.hasOwnProperty(d[group])) {
				// Add this as a new property
				res[d[group]] = [];
				// Create index for each label value
				for (let i = 0; i < labels.length; i++) {
					res[d[group]].push(0);
				}
			}
			// Find index corresponding to label
			let labelIndex = labels.indexOf(d[x]);
			// Affect value at this index
			res[d[group]][labelIndex] = d[y];
		}
		return res;
	}


	// ~

	// 'Group by' radio buttons 'on change' event handler
	$('input[name="group"]').on('change', () => {
		updateStatusData();
		updateQCwithCAData();
		let group = $('input[name="group"]:checked').val();
		switch (group) {
			case 'center':
				updateStatusDatasets('center');
				updateQCwithCADatasets('center');
				$('#form-group-display').removeAttr('hidden');
				$('#form-group-select-center').removeAttr('hidden');
				$('#form-group-select-country').attr('hidden', '');
				break;
			case 'country':
				updateStatusDatasets('country');
				updateQCwithCADatasets('country');
				$('#form-group-display').removeAttr('hidden');
				$('#form-group-select-country').removeAttr('hidden');
				$('#form-group-select-center').attr('hidden', '');
				break;
			default:
				$('#form-group-display').attr('hidden', '');
				$('#form-group-select-center').attr('hidden', '');
				$('#form-group-select-country').attr('hidden', '');
		}
	});


	// 'Centers' select 'on change' event handler
	select.center.dom.on('change', () => {
		updateStatusDatasets('center');
		updateQCwithCADatasets('center');
	});


	// 'Countries' select 'on change' event handler
	select.country.dom.on('change', () => {
		updateStatusDatasets('country');
		updateQCwithCADatasets('country');
	});


	// 'Variable ring thickness' checkbox 'on change' envent handler
	$('#chart-controls-number-qc-as-thickness').on('change', () => {
		for (let d of chartStatusObj.data.datasets) {
			if ($('#chart-controls-number-qc-as-thickness').is(':checked')) {
				d.weight = d.data.reduce((a, b) => a + b, 0);
			} else {
				d.weight = 1;
			}
		}
		chartStatusObj.update();

		for (let d of chartQCwithCAObj.data.datasets) {
			if ($('#chart-controls-number-qc-as-thickness').is(':checked')) {
				d.weight = d.data.reduce((a, b) => a + b, 0);
			} else {
				d.weight = 1;
			}
		}
		chartQCwithCAObj.update();
	});


	/**
	 * Update chart with the selected values
	 */
	function updateStatusDatasets(group) {
		let selected = select[group].dom.val();
		let displayed = objGetValuesX('name', chartStatusObj.data.datasets);

		var datasets = getDatasetsQCStatus(group, rawData);

		// Add missing datasets
		for (let s of selected) {
			if (!displayed.includes(s)) {

				// Add dataset
				chartStatusObj.data.datasets.push({
					name: s,
					data: datasets[s],
					backgroundColor: COLORS_BG_STATUS,
					weight: datasets[s].reduce((a, b) => a + b, 0)
				});
			}
		}
		// Remove surplus datasets
		for (let d of displayed) {
			if (!selected.includes(d)) {
				// Find and remove dataset
				for (let i = 0; i < chartStatusObj.data.datasets.length; i++) {
					if (chartStatusObj.data.datasets[i].name == d) {
						// Remove from array
						chartStatusObj.data.datasets.splice(i, 1);
					}
				}
			}
		}
		chartStatusObj.update();
	}
</script>



<script>
	var LABELS_QC_WITH_CA = ['With CA', 'Without CA'];
	var VALUES_QC_WITH_CA = [true, false];
	var COLORS_BG_QC_WITH_CA = ['#DF4D5B', '#92E6DC'];
	//var COLORS_TEXT_QC_WITH_CA = ['#381417', '#375753'];

	var chartQCwithCAObj = new Chart('chart-qc-with-ca', {
		type: 'pie',
		data: {
			labels: LABELS_QC_WITH_CA,
			datasets: []
		},
		options: {
			plugins: {
				datalabels: false
			},
			title: {
				text: 'Number of QC which had corrective actions',
				display: true
			},
			legend: {
				position: 'right'
			},
			cutoutPercentage: 10,
			tooltips: {
				callbacks: {
					// Custom label
					label: function(item, data) {
						let dataset = data.datasets[item.datasetIndex];
						let currentValue = dataset.data[item.index];

						let sum = 0;
						for (let value of dataset.data) {
							sum += value;
						}

						let percentage = ((currentValue / sum) * 100).toFixed(1);
						return [
							dataset.name,
							data.labels[item.index],
							`${currentValue} (${percentage} %)`
						];
					}
				}
			}
		}
	});

	$(document).ready(() => {
		updateQCwithCAData();
	});


	function formatQCwithCAData() {
		let group = $('input[name="group"]:checked').val();
		switch (group) {
			default:
				throw `Unknown group value.`;

			case 'all':
				var datasetData = [];

				for (let v of VALUES_QC_WITH_CA) {
					let queryResult = alasql(`SELECT count(*) AS nb FROM ? WHERE hasCorrectiveAction = ${v} OR hasCorrectiveAction LIKE "${v}"`, [rawData]);
					datasetData.push(queryResult[0].nb);
				}

				var datasets = [{
					name: 'All',
					data: datasetData,
					backgroundColor: COLORS_BG_QC_WITH_CA
				}];

				return {
					datasets: datasets
				};

			case 'center':
			case 'country':
				var rawDatasets = getDatasetsHasCorrectiveAction(group, rawData);
				var datasets = [];
				for (let grp in rawDatasets) {
					if ($('#chart-controls-number-qc-as-thickness').is(':checked')) {
						var weight = rawDatasets[grp].reduce((a, b) => a + b, 0);
					} else {
						var weight = 1;
					}
					datasets.push({
						name: grp,
						data: rawDatasets[grp],
						backgroundColor: COLORS_BG_QC_WITH_CA,
						weight: weight
					});
				}
				return {
					datasets: datasets
				};
		};
	}


	/**
	 * Returns an object where each property is a 'group' (center or country)
	 * containing an array of the corresponding number of occurences of the hasCorrectiveAction values
	 * @param {string} group property name of the group values
	 */
	function getDatasetsHasCorrectiveAction(group, data) {
		var hasCA = alasql(`SELECT ${group}, hasCorrectiveAction , COUNT(*) AS nb FROM ? GROUP BY ${group}, hasCorrectiveAction ORDER BY ${group}, hasCorrectiveAction`, [data]);
		return getDatasets(hasCA, 'hasCorrectiveAction', 'nb', VALUES_QC_WITH_CA, group);
	}

	function updateQCwithCAData(rawData) {
		let formatedData = formatQCwithCAData(rawData);
		chartQCwithCAObj.data.datasets = formatedData.datasets;
		chartQCwithCAObj.update();
	};


	/**
	 * Update chart with the selected values
	 */
	function updateQCwithCADatasets(group) {
		let selected = select[group].dom.val();
		let displayed = objGetValuesX('name', chartQCwithCAObj.data.datasets);

		var datasets = getDatasetsHasCorrectiveAction(group, rawData);

		// Add missing datasets
		for (let s of selected) {
			if (!displayed.includes(s)) {

				// Add dataset
				chartQCwithCAObj.data.datasets.push({
					name: s,
					data: datasets[s],
					backgroundColor: COLORS_BG_QC_WITH_CA,
					weight: datasets[s].reduce((a, b) => a + b, 0)
				});
			}
		}
		// Remove surplus datasets
		for (let d of displayed) {
			if (!selected.includes(d)) {
				// Find and remove dataset
				for (let i = 0; i < chartQCwithCAObj.data.datasets.length; i++) {
					if (chartQCwithCAObj.data.datasets[i].name == d) {
						// Remove from array
						chartQCwithCAObj.data.datasets.splice(i, 1);
					}
				}
			}
		}
		chartQCwithCAObj.update();
	}
</script>