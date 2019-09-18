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

<h1>Review Data</h1>

<div id="stats-alert"></div>

<nav id="nav-visit-type">
	<div class="nav nav-tabs" id="nav-visit-type-tab" role="tablist">
	</div>
</nav>

<div class="tab-content" id="nav-visit-type-panel">

</div>


<script type="text/javascript">
	var rawData = <?= $chartData ?>;
	if (rawData.data === undefined) {
		$('#stats-alert').append(`
		<div class="alert alert-info" role="alert">
			No data available
		</div>
		`);
		throw 'No data available';
	}

	var vTypes = {};
	var controls = {};
	var charts = {};

	$(document).ready(() => {
		vTypes = retrieveVisitTypes();

		for (vName in vTypes) {
			// Init nav bar
			controls[vName] = createNavItem(vName);

			// Set a property for each visit type in 'chart' object
			charts[vName] = {};

			// Create and fill chosenJS selects
			let dom;
			dom = controls[vName].selects.center.dom;
			controls[vName].selects.center = GaelOChart.createSelect(dom, rawData.data[vName], '_center');

			dom = controls[vName].selects.reviewer.dom;
			controls[vName].selects.reviewer = GaelOChart.createSelect(dom, rawData.data[vName], '_username');

			// Fill columns select on visit type change
			$(`#nav-tab-${vName}`).on('click', () => {
				retrieveColumnsList();
			});
			// Fill columns select on user type change
			$(`#form-${vName}-usertype`).on('change', () => {
				retrieveColumnsList();
			});
			// Fill columns select on user type change
			$(`#form-${vName}-formtype`).on('change', () => {
				retrieveColumnsList();
			});


			controls[vName].selects.center.dom.on('change', () => {
				updateCharts();
			});
			controls[vName].selects.reviewer.dom.on('change', () => {
				updateCharts();
			});
			controls[vName].selects.column.dom.on('change', () => {
				updateCharts();
			});
			$(`#form-${vName}-format`).on('change', () => {
				updateCharts();
			});
		}

		// Select first tab
		$('#nav-visit-type-tab a').first().click();
	});

	// ~

	/**
		Get the value type of the specific form entries.
		Returns an object where each property is a visit type and each
		inner property is a column name with the column type
	 */
	function retrieveVisitTypes() {
		let res = {};
		for (let vName in rawData.structureDetails) {
			if (rawData.data[vName] === undefined) {
				// No data, no need for retrieve it
				continue;
			}
			v = rawData.structureDetails[vName];
			res[vName] = {};
			for (col of v) {
				if (col['COLUMN_NAME'] == 'id_review') {
					// Skip column id_review
					continue;
				}
				// Retrieve column type
				if (col['COLUMN_TYPE'].indexOf('(') == -1) {
					var colType = col['COLUMN_TYPE'];
					var colParams = undefined;
				} else {
					// Column type has params
					var colType = col['COLUMN_TYPE'].split('(')[0];
					// Retrieves params as array
					if (colType == 'enum' || colType == 'set') {
						var colParams = col['COLUMN_TYPE'].substring(col['COLUMN_TYPE'].indexOf('(\'') + 2, col['COLUMN_TYPE'].indexOf('\')')).split('\',\'');
					} else {
						var colParams = col['COLUMN_TYPE'].substring(col['COLUMN_TYPE'].indexOf('(') + 1, col['COLUMN_TYPE'].indexOf(')')).split(',');
					}
				}

				// Only keep exploitable columns
				var COL_TYPE = ['decimal', 'enum', 'set', 'int', 'tinyint']
				if (COL_TYPE.includes(colType)) {
					res[vName][col['COLUMN_NAME']] = {
						name: col['COLUMN_NAME'],
						type: colType,
						typeParam: colParams
					}
				}

			}
		}
		return res;
	}

	/**
	 * Returns name of the selected visit type
	 */
	function getVType() {
		return $('#nav-visit-type-tab a.nav-item[aria-selected="true"]').attr("value");
	}

	/**
	 * Returns the current category group
	 * If 'usertype' is 'investigator' then category is 'center'
	 * If 'usertype' is 'reviewer' then category is 'username'
	 */
	function getCategory(vType = getVType()) {
		return isLocal(vType) ? '_center' : '_username';
	}

	/**
	 * Returns checked 'user type' button
	 */
	function isLocal(vType = getVType()) {
		return controls[vType].inputs.usertype.val() == 'investigator';
	}

	/**
	 * Returns checked 'form type' button
	 */
	function isAdjudication(vType = getVType()) {
		return controls[vType].inputs.formtype.val() == 'adjud';
	}

	/**
	 * Returns true if the 'pie chart thickness variable' input is checked
	 */
	function isPieChartsThicknessVariable(vType = getVType()) {
		return $(`#inp-${vType}-pie-chart-thickness-variable`).is(':checked');
	}

	/**
	 * Returns js object with a set of function
	 * to manipulate the tab panel elements
	 */
	function createNavItem(name) {
		let res = {};
		// Escape some special chars
		name = name.replace(/#|\.|:|'|"/g, '');

		// Append html nav item
		$('#nav-visit-type-tab').append(`
				<a class="nav-item nav-link" href="#${name}" id="nav-tab-${name}" data-toggle="tab" role="tab" aria-controls="nav-${name}" aria-selected="false" value="${name}">${name}</a>
			`);
		$(`#nav-tab-${name}`).on('click', () => {
			// Update 'aria-selected' value
			$(`#nav-visit-type-tab .nav-item`).attr('aria-selected', 'false');
			$(`#nav-tab-${name}`).attr('aria-selected', 'true');

			// Show associated tab content
			$(`#nav-visit-type-panel .tab-pane`).removeClass('show active');
			$(`#nav-${name}`).addClass('show active');
		});

		// Append associated tab content
		$('#nav-visit-type-panel').append(`
			<div class="tab-pane fade" id="nav-${name}" role="tabpanel" aria-labelledby="${name}-tab"></div>
		`);

		// Append radio buttons inputs
		let inputs = [{
				name: 'usertype',
				label: 'User type',
				attr: '',
				buttons: [{
						name: 'investigator',
						label: 'Investigator',
						attr: 'checked'
					},
					{
						name: 'reviewer',
						label: 'Reviewer',
						attr: ''
					}
				]
			},
			{
				name: 'formtype',
				label: 'Form type',
				attr: 'hidden',
				buttons: [{
						name: 'initial',
						label: 'Initial',
						attr: 'checked'
					},
					{
						name: 'adjud',
						label: 'Adjudication',
						attr: ''
					}
				]
			},
			{
				name: 'format',
				label: 'Formating',
				attr: '',
				buttons: [{
						name: 'filter',
						label: 'Filter data',
						attr: 'checked'
					},
					{
						name: 'group',
						label: 'Group data',
						attr: ''
					}
				]
			}
		];
		res.inputs = {};
		for (let i of inputs) {
			$(`#nav-${name}`).append(`
				<div id="form-${name}-${i.name}" class="form-group row" ${i.attr}>
					<label class="col-sm-2 col-form-label w-100">${i.label}</label>
				</div>
			`);
			for (let b of i.buttons) {
				$(`#form-${name}-${i.name}`).append(`
					<div class="form-check form-check-inline">
						<input id="inp-${b.name}-${name}" class="form-check-input" name="${i.name}-${name}" type="radio" value="${b.name}" ${b.attr}>
						<label for="inp-${b.name}-${name}" class="form-check-label">${b.label}</label>
					</div>
				`);
			}
			res.inputs[i.name] = {
				val: function() {
					return $(`input[name="${i.name}-${name}"]:checked`).val();
				}
			};
		}
		// On 'usertype' change, show/hide the 'formtype' input
		$(`#form-${name}-usertype`).on('change', () => {
			let val = res.inputs.usertype.val();
			if (val == 'investigator') {
				$(`#form-${name}-formtype`).attr('hidden', '');
			} else if (val == 'reviewer') {
				$(`#form-${name}-formtype`).removeAttr('hidden');
			}
		});

		// Append selects
		let selects = [{
				name: 'center',
				label: 'Centers',
				attr: ''
			},
			{
				name: 'reviewer',
				label: 'Reviewers',
				attr: 'hidden'
			},
			{
				name: 'column',
				label: 'Columns',
				attr: ''
			}
		];
		res.selects = {};
		for (let s of selects) {
			$(`#nav-${name}`).append(`
				<div id="form-${name}-${s.name}" class="form-group row" ${s.attr}>
					<label class="col-sm-2 col-form-label w-100">${s.label}</label>
					<div class="col-sm-10">
						<select name="${s.name}-${name}" id="${s.name}-${name}" multiple hidden></select>
						<button class="btn btn-sm btn-dark" onclick="controls[getVType()].selects.${s.name}.selectAll()">Select All</button>
						<button class="btn btn-sm btn-secondary" onclick="controls[getVType()].selects.${s.name}.deselectAll()">Deselect All</button>
					</div>
				</div>
			`);
			res.selects[s.name] = {
				dom: $(`#${s.name}-${name}`)
			};
		}
		// On 'usertype' change, show/hide the 'centers'/'reviewers' selects
		$(`#form-${name}-usertype`).on('change', () => {
			let val = res.inputs.usertype.val();
			if (val == 'investigator') {
				$(`#form-${name}-center`).removeAttr('hidden');
				$(`#form-${name}-reviewer`).attr('hidden', '');
			} else if (val == 'reviewer') {
				$(`#form-${name}-reviewer`).removeAttr('hidden');
				$(`#form-${name}-center`).attr('hidden', '');
			}
		});

		// Append 'pie chart thickness variable' checkbox
		$(`#nav-${name}`).append(`
			<div id="form-${name}-pie-chart-thickness-variable" class="form-group row">
				<label class="col-sm-2 col-form-label w-100">Display</label>
				<div class="form-check form-check-inline">
					<input id="inp-${name}-pie-chart-thickness-variable" class="form-check-input" type="checkbox" checked>
					<label for="inp-${name}-pie-chart-thickness-variable" class="form-check-label">Variable ring thickness (for pie charts)</label>
				</div>
			</div>
		`);
		// On 'pie chart thickness variable' change, update datasets weights
		$(`#inp-${name}-pie-chart-thickness-variable`).on('change', () => {
			for (let colName in charts[getVType()]) {
				let chart = charts[getVType()][colName];
				for (let dset of chart.obj.data.datasets) {
					if (dset.weight !== undefined) {
						dset.weight = (isPieChartsThicknessVariable()) ? dset.weightBak : 1;
					}
				}
				chart.obj.update();
			}
		});


		// Append charts div section
		$(`#nav-${name}`).append(`<div id="charts-${name}" class="row"></div>`);

		return res;
	}

	/**
	 * Load 'column' select
	 * Do not keep columns that contains null value only
	 */
	function retrieveColumnsList() {
		let cols = [];

		for (let col in vTypes[getVType()]) {
			if (isLocal()) {
				var qRes = alasql(`SELECT ${col}, COUNT(*) AS nb FROM ? WHERE _localForm = true GROUP BY ${col}`, [rawData.data[getVType()]]);
			} else {
				var qRes = alasql(`SELECT ${col}, COUNT(*) AS nb FROM ? WHERE _localForm = false AND _adjudicationForm = ${isAdjudication()} GROUP BY ${col}`, [rawData.data[getVType()]]);
			}
			// Check if the column is not empty
			if (qRes[0][col] != null || qRes.length !== 1) {
				cols.push(col);
			}
		}
		// Format array of string into array of object with a string property
		cols = listValuesXReverse('col', cols);

		// Create chosenJS obj
		dom = controls[getVType()].selects.column.dom;
		controls[getVType()].selects.column = GaelOChart.createSelect(dom, cols, 'col');
	}

	/**
	 * Update charts for each selected columns
	 */
	function updateCharts() {
		// Get format mode
		var formatMode = $(`input[name="format-${getVType()}"]:checked`).val();
		// Get category
		var category = getCategory();
		// Get selected columns
		var cols = {};
		var colsArray = controls[getVType()].selects.column.dom.val();
		for (var colName of colsArray) {
			cols[colName] = vTypes[getVType()][colName];
		}

		var data = cleanData();

		// Delete unwanted charts
		for (let colName in charts[getVType()]) {
			let chart = charts[getVType()][colName];
			if (cols[colName] === undefined) {
				// This chart should not be drawn
				chart.remove();
			}
		}

		// Draw chart for each column
		for (let colName in cols) {
			let col = cols[colName];

			switch (col.type) {
				case 'decimal':
					// Draw violin plot chart
					switch (formatMode) {

						case 'filter': // Draw violin plot chart with a unique violin
							var qRes = alasql(`SELECT ${colName} FROM ?`, [data]);
							if (qRes.length === 0) {
								if (charts[getVType()][colName] !== undefined) {
									charts[getVType()][colName].remove();
								}
								continue;
							}
							var labels = ['All'];
							// Prepare ChartJS dataset
							var color = GaelOChart.generateColor(9);
							var datasets = [{
								data: [listValuesX(colName, qRes)],
								backgroundColor: `rgba(${color.r},${color.g},${color.b},0.2)`,
								borderColor: color.hex,
								borderWidth: 1,
								padding: 10,
								itemRadius: 2
							}];
							drawViolinPlotChart(colName, labels, datasets);
							break;

						case 'group': // Draw violin plot chart with a violin for each category group
							var qRes = alasql(`SELECT ${category}, ${colName} FROM ? ORDER BY ${category}`, [data]);
							if (qRes.length === 0) {
								if (charts[getVType()][colName] !== undefined) {
									charts[getVType()][colName].remove();
								}
								continue;
							}
							var labels = listValuesX(category, alasql(`SELECT DISTINCT ${category} FROM ?`, [qRes]));
							// Gather data by category
							var datasetData = [];
							for (let l of labels) {
								datasetData.push(listValuesX(colName, alasql(`SELECT ${colName} FROM ? WHERE ${category} LIKE "${l}"`, [qRes])));
							}
							// Prepare ChartJS dataset
							var colors = {
								bg: [],
								border: []
							};
							for (let i = 0; i < labels.length; i++) {
								let c = GaelOChart.generateColor(i);
								colors.bg.push(`rgba(${c.r},${c.g},${c.b},0.2)`);
								colors.border.push(c.hex);
							}
							var datasets = [{
								data: datasetData,
								backgroundColor: colors.bg,
								borderColor: colors.border,
								borderWidth: 1,
								padding: 10,
								itemRadius: 2
							}];
							drawViolinPlotChart(colName, labels, datasets);
							break;
					}
					break;

				case 'enum':
				case 'set':
				case 'int':
				case 'tinyint':
					// Draw pie chart
					switch (formatMode) {

						case 'filter': // Draw pie chart with a unique dataset
							var qRes = alasql(`SELECT ${colName}, COUNT(*) AS nb FROM ? GROUP BY ${colName} ORDER BY ${colName}`, [data]);
							if (qRes.length === 0) {
								if (charts[getVType()][colName] !== undefined) {
									charts[getVType()][colName].remove();
								}
								continue;
							}
							var labels = listValuesX(colName, qRes);
							// Prepare ChartJS dataset
							var colors = [];
							for (let i = 0; i < labels.length; i++) {
								colors.push(GaelOChart.generateColor(i).hex);
							}
							var datasets = [{
								name: 'All',
								data: listValuesX('nb', qRes),
								backgroundColor: colors
							}];
							drawPieChart(colName, labels, datasets);
							break;

						case 'group': // Draw pie chart with a dataset for each category group
							var qRes = alasql(`SELECT ${category}, ${colName}, COUNT(*) AS nb FROM ? GROUP BY ${category}, ${colName} ORDER BY ${category}, ${colName}`, [data]);
							if (qRes.length === 0) {
								if (charts[getVType()][colName] !== undefined) {
									charts[getVType()][colName].remove();
								}
								continue;
							}
							var labels = listValuesX(colName, alasql(`SELECT DISTINCT ${colName} FROM ? ORDER BY ${colName}`, [qRes]));
							var rawDatasets = getDatasets(qRes, colName, 'nb', labels, category);
							// Prepare ChartJS dataset
							var colors = [];
							for (let i = 0; i < labels.length; i++) {
								colors.push(GaelOChart.generateColor(i).hex);
							}
							var datasets = [];
							for (let ctgory in rawDatasets) {
								if (isPieChartsThicknessVariable()) {
									var weight = rawDatasets[ctgory].reduce((a, b) => a + b, 0);
								} else {
									var weight = 1;
								}
								datasets.push({
									name: ctgory,
									data: rawDatasets[ctgory],
									weight: weight,
									weightBak: weight,
									backgroundColor: colors
								});
							}
							drawPieChart(colName, labels, datasets);
							break;

					}
					break;
			}
		}
	}

	/**
	 * Apply filters on raw data and returns the cleaned data
	 */
	function cleanData() {
		// Get category
		var category = getCategory();
		// Get selected category elements
		var selectedCat = isLocal() ? controls[getVType()].selects.center.dom.val() : controls[getVType()].selects.reviewer.dom.val();

		// Retrieve data from local/non-local forms
		if (isLocal()) {
			// Only get local forms
			var res = alasql(`
				SELECT * FROM ? data
				WHERE EXISTS (
					SELECT ${category} FROM ? selectedCat
					WHERE data.${category} LIKE selectedCat.${category}
				)
				AND _localForm = true
				ORDER BY ${category}
			`, [rawData.data[getVType()], listValuesXReverse(category, selectedCat)]);
		} else {
			// Only get non-local forms
			// & initial/adjudication forms
			var res = alasql(`
				SELECT * FROM ? data
				WHERE EXISTS (
					SELECT ${category} FROM ? selectedCat
					WHERE data.${category} LIKE selectedCat.${category}
				)
				AND _localForm = false
				AND _adjudicationForm = ${isAdjudication()}
				ORDER BY ${category}
			`, [rawData.data[getVType()], listValuesXReverse(category, selectedCat)]);

		}
		return res;
	}

	// ~

	function drawViolinPlotChart(colName, labels, datasets) {
		drawChart('violin', colName, labels, datasets);
	}

	function drawPieChart(colName, labels, datasets) {
		drawChart('pie', colName, labels, datasets);
	}

	function drawChart(chartType, colName, labels, datasets) {
		if (charts[getVType()][colName] !== undefined) {
			// Update chart dataset
			charts[getVType()][colName].obj.data = {
				labels: labels,
				datasets: datasets
			}
			charts[getVType()][colName].obj.update();

		} else {
			// Create a new chart

			// Add a chart as property in 'charts' global variable
			charts[getVType()][colName] = {
				obj: {},
				divID: `chart-div-${getVType()}-${colName}`,
				chartID: `chart-${getVType()}-${colName}`,
				type: chartType,
				userType: controls[getVType()].inputs.usertype.val(),
				formType: controls[getVType()].inputs.formtype.val(),
				formatMode: $(`input[name="format-${getVType()}"]:checked`).val(),

				remove: function() {
					this.obj.destroy();
					$(`#${this.divID}`).remove();
					delete charts[getVType()][colName];
				}
			};

			// Append html chart container
			$(`#charts-${getVType()}`).append(`
				<div id="${charts[getVType()][colName].divID}" class="col-lg-6" style="margin-bottom: 12px">
					<canvas id="${charts[getVType()][colName].chartID}"></canvas>
				</div>
			`);

			switch (chartType) {
				case 'violin':
					// Create violin plot chart
					charts[getVType()][colName].obj = new Chart(charts[getVType()][colName].chartID, {
						type: 'violin',
						data: {
							labels: labels,
							datasets: datasets
						},
						options: {
							title: {
								text: colName,
								display: true
							},
							legend: {
								display: false,
							},
							responsive: true,
							scales: {
								xAxes: [{
									maxBarThickness: 16,
									display: false
								}]
							},
							tooltips: {
								callbacks: {
									// Custom label
									label: function(item, data) {
										let dataset = data.datasets[item.datasetIndex];
										let currentValue = dataset.data[item.index];

										return [
											`${ss.mean(currentValue).toFixed(2)} | Mean`,
											`${ss.max(currentValue).toFixed(2)} | Max`,
											`${ss.quantile(currentValue, 0.75).toFixed(2)} | 3rd Quartile`,
											`${ss.quantile(currentValue, 0.5).toFixed(2)} | Median`,
											`${ss.quantile(currentValue, 0.25).toFixed(2)} | 1st Quartile`,
											`${ss.min(currentValue).toFixed(2)} | Min`
										];
									}
								}
							},
							plugins: {
								datalabels: false
							}
						}
					});
					break;

				case 'pie':
					// Create pie chart
					charts[getVType()][colName].obj = new Chart(charts[getVType()][colName].chartID, {
						type: 'pie',
						data: {
							labels: labels,
							datasets: datasets
						},
						options: {
							title: {
								text: colName,
								display: true
							},
							legend: {
								// Show legend if less than 15 items
								display: (labels.length < 15),
								position: 'right'
							},
							responsive: true,
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
							},
							plugins: {
								datalabels: false
							}
						}
					});
					break;
			}
		}
	}

	// ~

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
</script>