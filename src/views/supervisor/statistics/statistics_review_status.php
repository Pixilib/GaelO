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

<h1>Review Status</h1>

<div>
	<canvas id="chart-status"></canvas>
</div>

<h1 class="mt-5">Review Conclusion values</h1>

<div id="charts-conclusion" class="row"></div>

<script>
	var rawData = <?= $chartData ?>;

	var LABELS = ['Done', 'Not Done', 'Ongoing', 'Wait Adjudication'];
	var COLORS_BG = ['#9BEB48', '#FF5538', '#7A56FF', '#FFBF48'];
	var COLORS_TEXT = ['#38541A', '#632216', '#171030', '#473615'];

	var chartStatusObj = new Chart('chart-status', {
		type: 'pie',
		data: {
			labels: [],
			datasets: []
		},
		options: {
			cutoutPercentage: 25,
			legend: {
				position: 'right'
			},
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
							data.labels[item.index],
							`${currentValue} (${percentage} %)`
						];
					}
				}
			}
		}
	});

	var formatedData = formatStatusData(rawData);
	chartStatusObj.data.labels = formatedData.labels;
	chartStatusObj.data.datasets = formatedData.datasets;
	chartStatusObj.update();


	function formatStatusData(data) {
		let datasetData = [];
		for (let l of LABELS) {
			let queryResult = alasql(`SELECT status, count(*) as nb FROM ? WHERE status = "${l}" GROUP BY status ORDER BY status`, [data])
			datasetData.push(queryResult[0].nb);
		}

		return {
			labels: LABELS,
			datasets: [{
				data: datasetData,
				backgroundColor: COLORS_BG,
				datalabels: {
					formatter: (value, ctx) => {
						// Get percentage value
						let sum = 0;
						let dataArr = ctx.chart.data.datasets[0].data;
						dataArr.map(data => {
							sum += data;
						});
						return (value * 100 / sum).toFixed(1) + "%";
					},
					color: COLORS_TEXT
				}
			}]
		}
	}
</script>


<script>
	// Retrieve visit types list
	var vTypes = listValuesX('visitType', alasql('SELECT DISTINCT visitType FROM ? WHERE visitType IS NOT NULL ORDER BY visitType', [rawData]));
	var chartsConclObj = [];

	for (let v of vTypes) {
		// Retrieve all possible values of 'conclusionValue' for this visit type
		let labels = listValuesX('conclusionValue', alasql(`SELECT DISTINCT conclusionValue FROM ? WHERE visitType = "${v}" AND conclusionValue IS NOT NULL ORDER BY conclusionValue`, [rawData]));
		if (labels.length === 0) {
			// There is no data, skip this visit type
			continue;
		}

		// Generate array of colors
		let colors = [];
		for (let i = 0; i < labels.length; i++) {
			colors.push(GaelOChart.generateColor(i).hex);
		}

		// Format data
		let datasets = [{
			data: listValuesX('nb', alasql(`SELECT conclusionValue, count(*) as nb FROM ? WHERE visitType = "${v}" AND conclusionValue IS NOT NULL GROUP BY conclusionValue ORDER BY conclusionValue`, [rawData])),
			backgroundColor: colors,
		}];

		// Add chart div
		$('#charts-conclusion').append(`
			<div class="col-md-6">
				<canvas id="chart-${v}"></canvas>
			</div>
		`);

		// Generate chart
		chartsConclObj.push(new Chart(`chart-${v}`, {
			type: 'pie',
			data: {
				labels: labels,
				datasets: datasets
			},
			options: {
				cutoutPercentage: 25,
				legend: {
					position: 'right'
				},
				title: {
					text: v,
					display: true
				},
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
								data.labels[item.index],
								`${currentValue} (${percentage} %)`
							];
						}
					}
				},
				plugins: {
					datalabels: false
				}
			}
		}));
	}

	// Alert if empty
	if ($('#charts-conclusion').html() === '') {
		$('#charts-conclusion').append(`
		<div class="alert alert-info col-sm-12" role="alert">
			No data available
		</div>
		`);
	}
</script>