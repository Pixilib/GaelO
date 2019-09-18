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

<h1>Time interval between tracer uptake and acquisition</h1>

<div id="chart-controls">

	<div class="form-group row">
		<label class="col-sm-2 col-form-label w-100">Offset</label>
		<div class="form-inline">
			<input id="chart-controls-offset" class="form-control w-100" type="number" value="60">
		</div>
		<label class="col-sm-1 col-form-label w-100">Tolerance</label>
		<div class="form-inline">
			<div class="input-group">
				<div class="input-group-prepend">
					<span class="input-group-text">±</span>
					<input id="chart-controls-tolerance" class="form-control w-100" type="number" value="0" min="0">
				</div>
			</div>
		</div>
	</div>

	<div class="form-group row">
		<label class="col-sm-2 col-form-label w-100">Filter by</label>
		<div class="form-check form-check-inline">
			<input id="chart-controls-filter-none" class="form-check-input" name="filter" type="radio" value="none" checked>
			<label for="chart-controls-filter-none" class="form-check-label">None</label>
		</div>
		<div class="form-check form-check-inline">
			<input id="chart-controls-filter-center" class="form-check-input" name="filter" type="radio" value="center">
			<label for="chart-controls-filter-center" class="form-check-label">Center</label>
		</div>
		<div class="form-check form-check-inline">
			<input id="chart-controls-filter-country" class="form-check-input" name="filter" type="radio" value="country">
			<label for="chart-controls-filter-country" class="form-check-label">Country</label>
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
</div>

<div>
	<canvas id="chart"></canvas>
</div>

<div style="margin-top: 32px">
	<h1>Statistics</h1>
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


<script type="text/javascript">
	var rawData = <?= $chartData ?>;

	var chartObj = new Chart('chart', {
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
							res.push(`${currentValue.toFixed(1)} minutes`);
							return res;
						} else {
							return [
								`${currentValue.toFixed(1)} minutes`
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

	updateData();


	/**
	 * Performs data manipulation such as averaging and
	 * deleting useless data before updating the chart with
	 */
	function formatData(rawData) {
		// Clones data instead of cloning array pointer references
		let data = alasql('SELECT * FROM ?', [rawData]);

		// Process filter input value
		let filter = $('input[name="filter"]:checked').val();

		switch (filter) {
			default:
				throw `Unknown filter value.`;
			case 'none':
				var formatedData = alasql(`
					SELECT idVisit, patientNumber, visitType, delayAcquisition
					FROM ? ORDER BY idVisit, patientNumber`,
					[data]
				);
				formatedData = cleanData(formatedData);

				return {
					labels: listValuesX('idVisit', formatedData),
					datasetsData: listValuesX('delayAcquisition', formatedData),
					extraLabel: [listValuesX('patientNumber', formatedData),
						listValuesX('visitType', formatedData)
					]
				}
				break;

			case 'center':
			case 'country':
				var selectedLabels = listValuesXReverse(filter, select[filter].dom.val());

				var formatedData = alasql(`
					SELECT idVisit, patientNumber, ${filter}, visitType, delayAcquisition
					FROM ? data
					WHERE EXISTS (
						SELECT ${filter} FROM ? selectedLabels
						WHERE data.${filter} LIKE selectedLabels.${filter}
					)
					ORDER BY ${filter}`,
					[data, selectedLabels]
				);
				formatedData = cleanData(formatedData);

				return {
					labels: listValuesX('idVisit', formatedData),
						datasetsData: listValuesX('delayAcquisition', formatedData),
						extraLabel: [listValuesX('patientNumber', formatedData),
							listValuesX('visitType', formatedData),
							listValuesX(filter, formatedData)
						]
				}
		}
	}

	/**
	 * Apply offset and tolerance on data
	 * Only data out of the tolerance is keeped
	 */
	function cleanData(data) {
		let offset = $('#chart-controls-offset').val();
		let tolerance = $('#chart-controls-tolerance').val();
		let res = [];
		let iRes = 0; // index res
		for (let i = 0; i < data.length; i++) {
			if (Math.abs(data[i].delayAcquisition - offset) > tolerance) {
				res[iRes] = {};
				for (let prop in data[i]) {
					res[iRes][prop] = data[i][prop];
				}
				res[iRes].delayAcquisition = data[i].delayAcquisition - offset;
				iRes++;
			}
		}
		return res;
	}

	/**
	 * Update the chart datasets with formated data
	 */
	function updateData() {
		let formatedData = formatData(rawData);
		chartObj.data.labels = formatedData.labels;
		chartObj.data.datasets = [{
			data: formatedData.datasetsData,
			extraLabel: formatedData.extraLabel,
			label: 'Time (minutes)',
			showLine: false,
			fill: false,
			pointStyle: 'rect',
			pointRadius: 4,
			pointHoverRadius: 6,
			backgroundColor: '#bd2535'
		}];
		chartObj.update();
	}



	// ~

	// 'Filter by' radio buttons 'on change' event handler
	$('input[name="filter"]').on('change', () => {
		let filter = $('input[name="filter"]:checked').val();
		switch (filter) {
			case 'center':
				$('#form-group-select-center').removeAttr('hidden');
				$('#form-group-select-country').attr('hidden', '');
				break;
			case 'country':
				$('#form-group-select-country').removeAttr('hidden');
				$('#form-group-select-center').attr('hidden', '');
				break;
			default:
				$('#form-group-select-center').attr('hidden', '');
				$('#form-group-select-country').attr('hidden', '');
		}
		updateData();
	});


	// 'Centers' select 'on change' event handler
	select.center.dom.on('change', () => {
		updateData();
	});

	// 'Countries' select 'on change' event handler
	select.country.dom.on('change', () => {
		updateData();
	});

	// Update the chart with the inputted offset
	$('#chart-controls-offset').on('change', function() {
		updateData();
	});

	// Update the chart with the inputted offset
	$('#chart-controls-tolerance').on('change', function() {
		let isActivatedAnimations = chartObj.options.animation;
		chartObj.options.animation = false;
		updateData();
		chartObj.options.animation = isActivatedAnimations;
	});
</script>

<script>
	var centers = listValuesX('center', alasql('SELECT DISTINCT center FROM ? ORDER BY center', [rawData]));
	for (let c of centers) {
		let data = alasql(`SELECT delayAcquisition, country FROM ? WHERE center LIKE "${c}"`, [rawData]);
		$('#stats-centers tbody').append(`
				<tr>
					<th>${data[0].country}</th>
					<th>${c}</th>
					<td>${ss.mean(listValuesX('delayAcquisition', data)).toFixed(2)}</td>
					<td>${ss.quantile(listValuesX('delayAcquisition', data), 0.25).toFixed(2)}</td>
					<td>${ss.quantile(listValuesX('delayAcquisition', data), 0.50).toFixed(2)}</td>
					<td>${ss.quantile(listValuesX('delayAcquisition', data), 0.75).toFixed(2)}</td>
					<td>${ss.min(listValuesX('delayAcquisition', data)).toFixed(2)}</td>
					<td>${ss.max(listValuesX('delayAcquisition', data)).toFixed(2)}</td>
				</tr>
		`);
	}
	$('#stats-centers').DataTable({
		"bSortCellsTop": true,
		"scrollX": true
	});
</script>