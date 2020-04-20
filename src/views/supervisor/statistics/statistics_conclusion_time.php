<?php
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
?>

<h1>Conclusion Time</h1>

<div id="chart-controls">

	<div class="form-group row">
		<label class="col-sm-1 col-form-label w-100">Tolerance</label>
		<div class="form-inline">
			<input id="chart-controls-tolerance" class="form-control w-100" type="number" value="0" min="0">
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

			let formatedData = alasql(`
					SELECT idVisit, center, country, conclusionDelay
					FROM ?`,
				[data]
			);
			formatedData = cleanData(formatedData);

			return {
				labels: listValuesX('idVisit', formatedData),
				datasetsData: listValuesX('conclusionDelay', formatedData)
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
				if (Math.abs(data[i].conclusionDelay) > tolerance) {
					res[iRes] = {};
					for (let prop in data[i]) {
						res[iRes][prop] = data[i][prop];
					}
					res[iRes].conclusionDelay = data[i].conclusionDelay;
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

		// Update the chart with the inputted tolerance
		$('#chart-controls-tolerance').on('change', function() {
			let isActivatedAnimations = chartObj.options.animation;
			chartObj.options.animation = false;
			updateData();
			chartObj.options.animation = isActivatedAnimations;
		});
	</script>