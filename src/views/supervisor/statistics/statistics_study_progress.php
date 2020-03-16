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

<h1>Study Progress</h1>

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

<div>
	<canvas id="chart-fraction"></canvas>
</div>

<div class="form-group row" style="margin-top: 32px">
	<label class="col-sm-1 col-form-label w-100">Tolerance</label>
	<div class="form-inline">
		<input id="chart-controls-tolerance" class="form-control w-100" type="number" value="0" min="0">
	</div>
</div>

<div>
	<canvas id="chart-delay"></canvas>
</div>

<div style="margin-top: 32px">
	<h1 id="stats-title">Statistics</h1>
	<table id="stats-centers" class="table table-sm table-striped" style="text-align:center; width:100%">
		<thead>
			<tr>
				<th>Country</th>
				<th>Centers</th>
				<th>Mean</th>
				<th>1st Quartile</th>
				<th>Median</th>
				<th>3rd Quartile</th>
				<th>Min</th>
				<th>Max</th>
			</tr>
		</thead>
		<tbody>
		</tbody>
	</table>
</div>


<script>
	var rawData = <?= $chartData ?>;
	var rawDataFraction = rawData[0];

	var LABELS_FRACTION = ['Done', 'Should be done'];
	var COLORS_BG_FRACTION = ['#9BEB48', '#FF5538'];
	var COLORS_TXT_FRACTION = ['#2E4515', '#45170F'];

	var chartFractionObj = new Chart('chart-fraction', {
		type: 'bar',
		data: {
			labels: [],
			datasets: []
		},
		options: {
			title: {
				text: "Visit status repartition",
				display: true
			},
			tooltips: {
				mode: 'index',
				intersect: false
			},
			responsive: true,
			scales: {
				xAxes: [{
					maxBarThickness: 48,
					stacked: true
				}],
				yAxes: [{
					stacked: true
				}]
			}
		}
	});

	// Init select inputs
	var select = {
		center: GaelOChart.createSelect(
			$('#chart-controls-select-center'),
			rawDataFraction,
			'center', {
				xGroup: 'country'
			}
		),
		country: GaelOChart.createSelect(
			$('#chart-controls-select-country'),
			rawDataFraction,
			'country'
		)
	}

	$(document).ready(() => {
		updateFractionData();
	});



	formatFractionData = (rawDataFraction) => {
		let group = $('input[name="group"]:checked').val();
		switch (group) {
			default:
				throw `Unknown group value.`;

			case 'all':
				var datasets = [];

				for (let l of LABELS_FRACTION) {

					let datasetsData = alasql(`
						SELECT COUNT(*) as nb FROM ? WHERE status LIKE "${l}"
					`, [rawDataFraction]);

					datasets.push({
						label: `# of ${l}`,
						data: listValuesX('nb', datasetsData),
						backgroundColor: COLORS_BG_FRACTION[LABELS_FRACTION.indexOf(l)],
						datalabels: {
							formatter: (value, ctx) => {
								let sum = 0;
								let datasets = ctx.chart.data.datasets;
								datasets.map(d => {
									sum += d.data[0];
								});
								return (value * 100 / sum).toFixed(1) + "%";
							},
							color: COLORS_TXT_FRACTION[LABELS_FRACTION.indexOf(l)]
						}
					});
				}

				return {
					labels: ['All'],
						datasets: datasets
				};

			case 'center':
			case 'country':
				var rawDataFractionsets = getDatasetsVisitStatus(group, rawDataFraction);
				var datasets = [];

				for (let l of LABELS_FRACTION) {
					datasets.push({
						label: `# of ${l}`,
						data: [],
						backgroundColor: COLORS_BG_FRACTION[LABELS_FRACTION.indexOf(l)],
						datalabels: {
							formatter: () => {
								return '';
							}
						}
					});
				}

				for (let grp in rawDataFractionsets) {
					for (let i = 0; i < LABELS_FRACTION.length; i++) {
						datasets[i].data.push(rawDataFractionsets[grp][i]);
					}
				}
				return {
					labels: objGetProperties(rawDataFractionsets),
						datasets: datasets
				};
		}
	}


	/**
	 * Returns an object where each property is a 'group' (center or country)
	 * containing an array of the corresponding number of occurences of the visit status values
	 * @param {string} group property name of the group values
	 */
	function getDatasetsVisitStatus(group, data) {
		let selectedLabels = listValuesXReverse(group, select[group].dom.val());
		var vStatus = alasql(`
			SELECT ${group}, status , COUNT(*) AS nb FROM ? data
			WHERE EXISTS (
				SELECT ${group} FROM ? selectedLabels
				WHERE selectedLabels.${group} LIKE data.${group}
			)
			GROUP BY ${group}, status ORDER BY ${group}, status`, [data, selectedLabels]);
		return getDatasets(vStatus, 'status', 'nb', LABELS_FRACTION, group);
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


	updateFractionData = () => {
		let formatedData = formatFractionData(rawDataFraction);
		chartFractionObj.data.labels = formatedData.labels;
		chartFractionObj.data.datasets = formatedData.datasets;
		chartFractionObj.update();
	}



	// ~

	// 'Group by' radio buttons 'on change' event handler
	$('input[name="group"]').on('change', () => {
		updateFractionData();
		updateDelayData();
		let group = $('input[name="group"]:checked').val();
		switch (group) {
			case 'center':
				$('#form-group-display').removeAttr('hidden');
				$('#form-group-select-center').removeAttr('hidden');
				$('#form-group-select-country').attr('hidden', '');
				break;
			case 'country':
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
		updateFractionData();
		updateDelayData();
	});


	// 'Countries' select 'on change' event handler
	select.country.dom.on('change', () => {
		updateFractionData();
		updateDelayData();
	});
</script>




<script>
	var rawDataDelay = rawData[1];
	var chartDelayObj = new Chart('chart-delay', {
		type: 'line',
		data: {
			labels: [],
			datasets: []
		},
		options: {
			plugins: {
				datalabels: false
			},
			tooltips: {
				callbacks: {
					// Custom label
					label: function(item, data) {
						let dataset = data.datasets[item.datasetIndex];
						let currentValue = dataset.data[item.index];

						if (Array.isArray(dataset.extraLabel)) {
							let res = [];
							for (let e of dataset.extraLabel) {
								res.push(e[item.index]);
							}
							res.push(`${currentValue.toFixed(1)} days`);
							return res;
						} else {
							return [
								`${currentValue.toFixed(1)} days`
							];
						}
					}
				}
			},
			scales: {
				xAxes: [{
					display: false
				}]
			}
		}
	});

	$(document).ready(() => {
		updateDelayData();
	});



	// ~


	/**
	 * Performs data manipulation such as averaging and
	 * deleting useless data before updating the chart with
	 */
	function formatDelayData(rawData) {
		// Clones data instead of cloning array pointer references
		let data = alasql('SELECT * FROM ?', [rawData]);

		// Process group input value
		let group = $('input[name="group"]:checked').val();

		switch (group) {
			default:
				throw `Unknown group value.`;
			case 'all':
				var formatedData = alasql(`
					SELECT idVisit, visitType, uploadDelay
					FROM ? ORDER BY idVisit`,
					[data]
				);
				formatedData = cleanDelayData(formatedData);

				return {
					labels: listValuesX('idVisit', formatedData),
						datasetsData: listValuesX('uploadDelay', formatedData),
						extraLabel: [listValuesX('visitType', formatedData)]
				}
				break;

			case 'center':
			case 'country':
				var selectedLabels = listValuesXReverse(group, select[group].dom.val());

				var formatedData = alasql(`
					SELECT idVisit, ${group}, visitType, uploadDelay
					FROM ? data
					WHERE EXISTS (
						SELECT ${group} FROM ? selectedLabels
						WHERE data.${group} LIKE selectedLabels.${group}
					)
					ORDER BY ${group}`,
					[data, selectedLabels]
				);
				formatedData = cleanDelayData(formatedData);

				return {
					labels: listValuesX('idVisit', formatedData),
						datasetsData: listValuesX('uploadDelay', formatedData),
						extraLabel: [listValuesX('visitType', formatedData),
							listValuesX(group, formatedData)
						]
				}
		}
	}

	/**
	 * Apply offset and tolerance on data
	 * Only data out of the tolerance is keeped
	 */
	function cleanDelayData(data) {
		let tolerance = $('#chart-controls-tolerance').val();
		let res = [];
		let iRes = 0; // index res
		for (let i = 0; i < data.length; i++) {
			if (Math.abs(data[i].uploadDelay) > tolerance) {
				res[iRes] = {};
				for (let prop in data[i]) {
					res[iRes][prop] = data[i][prop];
				}
				res[iRes].uploadDelay = data[i].uploadDelay;
				iRes++;
			}
		}
		return res;
	}


	/**
	 * Update the chart datasets with formated data
	 */
	function updateDelayData() {
		let formatedData = formatDelayData(rawDataDelay);
		chartDelayObj.data.labels = formatedData.labels;
		chartDelayObj.data.datasets = [{
			data: formatedData.datasetsData,
			extraLabel: formatedData.extraLabel,
			label: 'Time (days)',
			showLine: false,
			fill: false,
			pointStyle: 'rect',
			pointRadius: 4,
			pointHoverRadius: 6,
			backgroundColor: '#bd2535'
		}];
		chartDelayObj.update();
	}

	// ~

	// Update the chart with the inputted offset
	$('#chart-controls-tolerance').on('change', function() {
		let isActivatedAnimations = chartDelayObj.options.animation;
		chartDelayObj.options.animation = false;
		updateDelayData();
		chartDelayObj.options.animation = isActivatedAnimations;
	});
</script>



<script>
	updateTable();
	$('#stats-centers').DataTable({
		"scrollX": true
	});

	function updateTable() {
		$('#stats-centers tbody').empty();

		var centers = listValuesX('center', alasql(`SELECT DISTINCT center FROM ? ORDER BY center`, [rawDataDelay]));
		for (let c of centers) {
			let data = alasql(`SELECT uploadDelay, country FROM ? WHERE center LIKE "${c}"`, [rawDataDelay]);
			$('#stats-centers tbody').append(`
					<tr>
						<th>${data[0].country}</th>
						<th>${c}</th>
						<td>${ss.mean(listValuesX('uploadDelay', data)).toFixed(2)}</td>
						<td>${ss.quantile(listValuesX('uploadDelay', data), 0.25).toFixed(2)}</td>
						<td>${ss.quantile(listValuesX('uploadDelay', data), 0.50).toFixed(2)}</td>
						<td>${ss.quantile(listValuesX('uploadDelay', data), 0.75).toFixed(2)}</td>
						<td>${ss.min(listValuesX('uploadDelay', data)).toFixed(2)}</td>
						<td>${ss.max(listValuesX('uploadDelay', data)).toFixed(2)}</td>
					</tr>
			`);
		}
	}
</script>