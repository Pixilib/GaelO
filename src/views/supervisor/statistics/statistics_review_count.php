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

<h1>Review Count</h1>

<div id="form-group-select-reviewer" class="form-group row select">
	<label class="col-sm-2 col-form-label w-100">Reviewers</label>
	<div class="col-sm-10">
		<select name="reviewer" id="chart-controls-select-reviewer" multiple></select>
		<button class="btn btn-sm btn-dark" onclick="select.reviewer.selectAll()">Select All</button>
		<button class="btn btn-sm btn-secondary" onclick="select.reviewer.deselectAll()">Deselect All</button>
	</div>
</div>

<div id="form-group-display" class="form-group row">
	<label class="col-sm-2 col-form-label w-100">Display</label>
	<div class="form-check form-check-inline">
		<input id="chart-controls-stack-curves" class="form-check-input" type="checkbox" checked>
		<label for="chart-controls-stack-curves" class="form-check-label">Stack curves</label>
	</div>
</div>

<div>
	<canvas id="chart-by-months"></canvas>
</div>

<div style="margin-top: 32px">
	<canvas id="chart-by-reviewers"></canvas>
</div>


<script type="text/javascript">
	var rawData = <?= $chartData ?>;

	//Create Chart with Chart.js library
	var chartByMonthObj = new Chart('chart-by-months', {
		type: 'line',
		data: {
			labels: [],
			datasets: []
		},
		options: {
			plugins: {
				datalabels: false
			},
			title: {
				text: "Number of reviews by months",
				display: true
			},
			scales: {
				yAxes: [{
					ticks: {
						min: 0,
						beginAtZero: true
					},
					stacked: true
				}]
			}
		}
	});

	// Init select inputs
	var select = {
		reviewer: GaelOChart.createSelect(
			$('#chart-controls-select-reviewer'),
			rawData,
			'username'
		)
	}

	$(document).ready(() => {
		updateByMonthData();
	});



	/**
	 * Retrieve months list and count number of reviews grouped by months
	 * for each reviewer then returns labels list and datasets array
	 */
	formatByMonthData = (rawData) => {
		const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun',
			'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'
		];

		let res = {
			labels: [],
			datasets: []
		}

		let data = [];

		// Format date string
		for (let i = 0; i < rawData.length; i++) {
			let dateObj = new Date(rawData[i].date);
			data.push({
				username: rawData[i].username,
				dateYearMonth: rawData[i].date.substr(0, 7),
				dateYearMonthLiteral: months[dateObj.getMonth()] + ' ' + dateObj.getFullYear()
			})
		}

		// Retrieving labels
		res.labels = alasql(`SELECT DISTINCT dateYearMonthLiteral, dateYearMonth FROM ? ORDER BY dateYearMonth`, [data]);

		// Generating datasets
		const usernames = select.reviewer.dom.val();
		const yMonths = listValuesX('dateYearMonth', alasql(`SELECT DISTINCT dateYearMonth FROM ? ORDER BY dateYearMonth`, [data]));

		let colorIndex = 0;
		for (let u of usernames) {
			let userNbReviews = [];
			// Retrieve nb of reviews by months
			for (let ym of yMonths) {
				let userdata = alasql(`
					SELECT username, dateYearMonthLiteral, dateYearMonth, COUNT(*) AS nb
					FROM ? WHERE username = "${u}" AND dateYearMonth = "${ym}"
					GROUP BY username, dateYearMonthLiteral, dateYearMonth
					ORDER BY dateYearMonth`, [data]);
				userNbReviews.push(userdata[0].nb);
			}

			let color = GaelOChart.generateColor(colorIndex);

			res.datasets.push({
				label: u,
				data: userNbReviews,
				borderColor: color.hex,
				backgroundColor: GaelOColor.luminance(color.hex, 0.5)
			});
			colorIndex++;
		}
		return res;
	}


	/**
	 * Update the chart datasets with formated data
	 */
	updateByMonthData = () => {
		let formatedData = formatByMonthData(rawData);
		chartByMonthObj.data.labels = listValuesX('dateYearMonthLiteral', formatedData.labels);
		chartByMonthObj.data.datasets = formatedData.datasets;

		if (chartByMonthObj.options.scales.yAxes[0].stacked) {
			// Colorize area
			let i = 0;
			for (let d of chartByMonthObj.data.datasets) {
				d.backgroundColor = GaelOColor.luminance(GaelOChart.generateColor(i).hex, 0.5);
				i++;
			}
		} else {
			// Uncolorize area
			for (let d of chartByMonthObj.data.datasets) {
				d.backgroundColor = 'rgba(0,0,0,0)';
			}
		}

		chartByMonthObj.update();
	}


	// ~

	// 'Reviewers' select 'on change' event handler
	select.reviewer.dom.on('change', () => {
		updateByMonthData();
		updateByReviewersData();
	});


	// 'Reviewers' select 'on change' event handler
	$("#chart-controls-stack-curves").on('change', () => {
		if ($("#chart-controls-stack-curves").is(':checked')) {
			chartByMonthObj.options.scales.yAxes[0].stacked = true;
			// Colorize area
			let i = 0;
			for (let d of chartByMonthObj.data.datasets) {
				d.backgroundColor = GaelOColor.luminance(GaelOChart.generateColor(i).hex, 0.5);
				i++;
			}
		} else {
			chartByMonthObj.options.scales.yAxes[0].stacked = false;
			// Uncolorize area
			for (let d of chartByMonthObj.data.datasets) {
				d.backgroundColor = 'rgba(0,0,0,0)';
			}
		}
		chartByMonthObj.update();
	});
</script>



<script>
	//Create Chart with Chart.js library
	var chartByReviewers = new Chart('chart-by-reviewers', {
		type: 'bar',
		data: {
			labels: [],
			datasets: []
		},
		options: {
			title: {
				text: "Number of reviews by reviewers",
				display: true
			},
			legend: {
				display: false
			},
			scales: {
				xAxes: [{
					maxBarThickness: 32
				}],
				yAxes: [{
					ticks: {
						min: 0,
						beginAtZero: true
					}
				}]
			}
		}
	});

	$(document).ready(() => {
		updateByReviewersData();
	});



	formatByReviewersData = (rawData) => {
		let res = {
			labels: select.reviewer.dom.val(),
			datasets: []
		}

		let labels = listValuesXReverse('username', res.labels);

		let formatedData = alasql(`
			SELECT username, COUNT(*) as nb
			FROM ? rawData WHERE EXISTS (
				SELECT username FROM ? labels
				WHERE rawData.username LIKE labels.username
			) GROUP BY username ORDER BY username`, [rawData, labels]);

		let colors = [];
		let colorsTxt = [];
		for (let i = 0; i < formatedData.length; i++) {
			colors.push(GaelOChart.generateColor(i).hex);
			colorsTxt.push(GaelOColor.luminance(colors[i], -0.7));
		}

		res.datasets = [{
			label: '# of reviews',
			data: listValuesX('nb', formatedData),
			backgroundColor: colors,
			datalabels: {
				color: colorsTxt
			}
		}];
		return res;
	}

	updateByReviewersData = () => {
		let formatedData = formatByReviewersData(rawData);
		chartByReviewers.data.labels = formatedData.labels;
		chartByReviewers.data.datasets = formatedData.datasets;
		chartByReviewers.update();
	}
</script>