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

<h1>Quality Control Time</h1>

<div id="chart-controls">

	<div class="form-group row">
		<label class="col-sm-1 col-form-label w-100">Tolerance</label>
		<div class="form-inline">
			<input id="chart-controls-tolerance" class="form-control w-100" type="number" value="0" min="0">
		</div>
	</div>

	<div class="form-group row">
		<label class="col-sm-2 col-form-label w-100">Show</label>
		<div class="form-check form-check-inline">
			<input id="chart-controls-show-all" class="form-check-input" name="show" type="radio" value="all" checked>
			<label for="chart-controls-show-all" class="form-check-label">All</label>
		</div>
		<div class="form-check form-check-inline">
			<input id="chart-controls-show-withCA" class="form-check-input" name="show" type="radio" value="withCA">
			<label for="chart-controls-show-withCA" class="form-check-label">QC with corrective action</label>
		</div>
		<div class="form-check form-check-inline">
			<input id="chart-controls-show-withoutCA" class="form-check-input" name="show" type="radio" value="withoutCA">
			<label for="chart-controls-show-withoutCA" class="form-check-label">QC without corrective action</label>
		</div>
	</div>

	<div>
		<canvas id="chart"></canvas>
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

							return `${currentValue.toFixed(1)} days`;
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

		updateData();


		/**
		 * Performs data manipulation such as averaging and
		 * deleting useless data before updating the chart with
		 */
		function formatData(rawData) {
			// Clones data instead of cloning array pointer references
			let data = alasql('SELECT * FROM ?', [rawData]);

			var formatedData = alasql(`
					SELECT idVisit, center, country, qcDelay, hasCorrectiveAction
					FROM ?`,
				[data]
			);
			formatedData = cleanData(formatedData);

			return {
				labels: listValuesX('idVisit', formatedData),
				datasetsData: listValuesX('qcDelay', formatedData)
			}
		}

		/**
		 * Apply tolerance on data
		 * Only data out of the tolerance is keeped
		 */
		function cleanData(data) {
			let res = [];

			// Only keep out of tolerance data
			let tolerance = $('#chart-controls-tolerance').val();
			let iRes = 0; // index res
			for (let i = 0; i < data.length; i++) {
				if (Math.abs(data[i].qcDelay) > tolerance) {
					res[iRes] = {};
					for (let prop in data[i]) {
						res[iRes][prop] = data[i][prop];
					}
					res[iRes].qcDelay = data[i].qcDelay;
					iRes++;
				}
			}

			// Process 'show" selector value
			let show = $('input[name="show"]:checked').val();
			switch (show) {
				case 'withCA':
					res = alasql('SELECT * FROM ? WHERE hasCorrectiveAction LIKE "true" OR hasCorrectiveAction = true', [res]);
					break;
				case 'withoutCA':
					res = alasql('SELECT * FROM ? WHERE hasCorrectiveAction LIKE "false" OR hasCorrectiveAction = false', [res]);
					break;
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
				label: 'Time (days)',
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
		$('input[name="show"]').on('change', () => {
			updateData();
		});

		// Update the chart with the inputted tolerance
		$('#chart-controls-tolerance').on('change', function() {
			let isActivatedAnimations = chartObj.options.animation;
			chartObj.options.animation = false;
			updateData();
			chartObj.options.animation = isActivatedAnimations;
		});
	</script>