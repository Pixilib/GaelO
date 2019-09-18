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

<div id="vbuild" class="w-700 mx-auto">

	<h1>Visit Builder</h1>

	<div class="form-group row">
		<label for="vbuild-study" class="col-sm-2 col-form-label">Study</label>
		<select id="vbuild-study" class="col-sm-10 form-control">
		</select>
	</div>

	<div class="form-group row">
		<label for="vbuild-visit-type" class="col-sm-2 col-form-label">Visit type</label>
		<select id="vbuild-visit-type" class="col-sm-10 form-control">
		</select>
	</div>

	<div class="d-flex flex-row-reverse">
		<button id="vbuild-commit" class="btn btn-success mb-2" onclick="commitChanges()" hidden>Commit changes</button>
	</div>

	<div id="vbuild-alert"></div>

	<div>
		<table class="table table-sm table-responsive" hidden>
			<thead>
				<tr>
					<th colspan=3><button id="vbuild-add" role="add" class="btn btn-xs btn-primary" onclick="addNewCol()" style="width: 100%">Add a property</button></th>
					<th>Property name</th>
					<th style="min-width: 85px">Type</th>
					<th>Type param</th>
				</tr>
			</thead>
			<tbody id="vbuild-columns">
			</tbody>
		</table>
	</div>



</div>


<script type="text/javascript">
	var defaultStudy, defaultVType;

	var study = {
		dom: $('#vbuild-study'),
		val: function() {
			return this.dom.val();
		},
		opts: {}
	}

	var vTypes = {
		dom: $('#vbuild-visit-type'),
		val: function() {
			return this.dom.val();
		},
		opts: {}
	};

	var COL_TYPES = ['int', 'decimal', 'date', 'enum', 'varchar', 'tinytext', 'tinyint'];

	var cols = {
		hasChanges: function(colName) {
			if (colName !== undefined) {
				for (let p in this.changes[colName]) {
					return true;
				}
			} else {
				for (let p in this.changes) {
					return true;
				}
			}
			return false;
		},
		addChange: function(colName, property, value) {
			if (this.changes[colName] === undefined) {
				this.changes[colName] = {};
			}
			this.changes[colName][property] = value;
		},
		delChange: function(colName, property) {
			if (this.changes[colName] !== undefined) {
				if (this.changes[colName][property] !== undefined) {
					delete this.changes[colName][property];
				}
			}
			if (!this.hasChanges(colName)) {
				delete this.changes[colName];
			}
		},
		getChange: function(colName, property) {
			if (this.changes[colName] === undefined) {
				return undefined;
			}
			return this.changes[colName][property];
		},
		resetChanges: function(colName) {
			if (this.changes[colName] !== undefined) {
				delete this.changes[colName];
			}
		},
		reset: function() {
			this.changes = {};
			this.new = [];
		},
		changes: {},
		new: []
	};

	$(document).ready( () => {
		// Get list of studies
		retrieveStudies((studies) => {
			// Update 'studies' select
			study.dom.html('<option selected></option>');
			// Fill 'studies' select
			for (let st of studies) {
				study.dom.append(`
					<option value="${st}">${st}</option>
				`);
			}
			// Select study
			if (defaultStudy !== undefined) {
				study.dom.val(defaultStudy);
				study.dom[0].dispatchEvent(new Event('change'));
			}
		});


		// Ask confirmation when user is leaving
		study.dom.on('click', () => {
			if (cols.hasChanges()) {
				alertDiscardingChanges(() => cols.reset());
			}
		});
		// Ask confirmation when user is leaving
		vTypes.dom.on('click', () => {
			if (cols.hasChanges()) {
				alertDiscardingChanges(() => cols.reset());
			}
		});

		// Update 'visit type' select when 'study' changes
		study.dom.on('change', function() {
			let selectedStudy = study.val();
			retrieveVisitTypes(selectedStudy, () => {
				vTypes.dom.html('<option selected></option>');
				// Fill 'visit types' select
				for (let vTypeName in vTypes.opts) {
					vTypes.dom.append(`
						<option value="${vTypeName}">${vTypeName}</option>
					`);
				}
				// Select visit type
				if (defaultVType !== undefined) {
					vTypes.dom.val(defaultVType);
					vTypes.dom[0].dispatchEvent(new Event('change'));
				}
			});
			// Empty 'columns' table
			$('#vbuild-columns').empty();
		});

		// Update 'columns' table when 'visit type' changes
		vTypes.dom.on('change', function() {
			if (vTypes.val() == '') {
				$('#vbuild table, #vbuild-commit').attr('hidden', '');
				$('#vbuild-alert').html('');
			} else {
				checkTableIsEmpty(study.val(), vTypes.val(), (isTableEmpty) => {
					$('#vbuild-columns').empty();
					$('#vbuild table, #vbuild-commit').removeAttr('hidden');
					for (let colName in vTypes.opts[vTypes.val()]) {
						let col = vTypes.opts[vTypes.val()][colName];
						addExistingCol(colName, col);
					}
					if (!isTableEmpty) {
						$('#vbuild-alert').html(`
							<div class="alert alert-warning" role="alert">
								Database table is not empty therefore you cannot edit the table.
							</div>
						`);
						$('#vbuild-commit').attr('hidden', '');
						$('#vbuild-add').attr('hidden', '');
						$('#vbuild-columns').find('button[role="toggle"], button[role="edit"], button[role="reset"]').attr('hidden', '');
					} else {
						$('#vbuild-alert').html('');
						$('#vbuild-commit').removeAttr('hidden');
						$('#vbuild-add').removeAttr('hidden');
						$('#vbuild-columns').find('button[role="toggle"], button[role="edit"], button[role="reset"]').removeAttr('hidden');

						// Commit changes on select change
						$('#vbuild-columns').find('select').on('change', () => {
							updateChanges();
						});
					}

				});
			}
		});
	});

	/**
	 * Add a row in the 'column' table
	 */
	function addNewCol() {
		let timestamp = Date.now() % 1000000;
		let newCol = {
			id: `new${timestamp}`,
			name: `new${timestamp}`,
			type: 'int',
			typeParam: []
		};
		cols.new.push(newCol);

		// Add html
		$('#vbuild-columns').prepend(`
			<tr column="${newCol.id}" locked="true">
				<td colspan="2"><button role="toggle" class="btn btn-xs btn-danger" style="width: 100%">Remove</button></td>
				<td></td>
				<td><input class="form-control form-control-sm" type="text" name="name" pattern="^\\S+$" title="Whitespace are not allowed" required></td>
				<td><select class="form-control form-control-sm" type="text" name="type" required></select></td>
				<td name="type-param"></td>
			</tr>
		`);

		let tr = $(`tr[column="${newCol.name}"]`);

		// Select text in 'property name'
		tr.find('input[name="name"]').val('');
		tr.find('input[name="name"]').select();


		// Fill 'col type' select
		for (let type of COL_TYPES) {
			tr.find(`select[name="type"]`).append(`
				<option value="${type}">${type}</option>
			`);
		}

		// Commit changes on input change
		tr.find('input').on('change', () => {
			updateChanges();
		});

		// 'col type' select 'onchange', update 'type param' cell
		tr.find(`select[name="type"]`).on('change', () => {
			updateChanges();
			let type = tr.find(`select[name="type"]`).val();
			switch (type) {
				case 'date':
				case 'tinytext':
				case 'tinyint':
				case 'int':
					// No type param
					tr.find(`td[name="type-param"]`).html('');
					newCol.typeParam = [];
					break;

				case 'varchar':
					tr.find(`td[name="type-param"]`).html(`
						<input class="form-control form-control-sm" type="text" name="type-param" value="" pattern="[0-9]{1,}" required data-container="body" data-toggle="popover" data-trigger="focus" data-placement="top" data-content="Number of characters.">
					`);
					break;

				case 'decimal':
					tr.find(`td[name="type-param"]`).html(`
						<div class="row">
							<div class="col-6 pr-1">
								<input class="form-control form-control-sm" type="text" name="type-param-precision" value="" pattern="[0-9]{1,}" required data-container="body" data-toggle="popover" data-trigger="focus" data-placement="top" data-content="Number of significant digits.">
							</div>
							<div class="col-6 pl-1">
								<input class="form-control form-control-sm" type="text" name="type-param-scale" value="" pattern="[0-9]{1,}" required data-container="body" data-toggle="popover" data-trigger="focus" data-placement="top" data-content="Number of digits that can be stored following the decimal point.">
							</div>
						</div>
					`);
					break;

				case 'enum':
					tr.find(`td[name="type-param"]`).html(`
						<input class="form-control form-control-sm" type="text" name="type-param-scale" value="" required data-container="body" data-toggle="popover" data-trigger="focus" data-placement="top" data-content="List of permitted values. Each entry is comma-separated. Whitespaces are allowed.">
					`);
					break;
			}

			// Commit changes on input change
			tr.find('td[name="type-param"]').find('input').on('change', () => {
				updateChanges();
			});

			// Trigger html pattern checking by refreshing the inputs
			let inputs = tr.find(`td[name="type-param"] input`);
			for (let i of inputs) {
				// Refresh the input
				i.value = '';
			}

			// Enabling popover
			tr.find(`[data-toggle="popover"]`).popover();

		});

		// 'Delete column' button 'onclick' handler
		tr.find(`button[role="toggle"]`).on('click', () => {
			// Remove from 'new col' array
			cols.new.splice(cols.new.indexOf(newCol), 1);
			tr.remove();
		});

		// 'Edit/Lock column' button 'onclick' handler
		tr.find(`button[role="edit"]`).on('click', () => {
			let btn = tr.find(`button[role="edit"]`);
			let btnReset = tr.find(`button[role="reset"]`);
			if (tr.attr('locked') == 'false') {
				tr.attr('locked', 'true');
				tr.find('input').attr('readonly', '');
				tr.find('select').attr('disabled', '');
				btn.html('Edit');
				btnReset.attr('disabled', '');
			} else {
				tr.attr('locked', 'false');
				tr.find('input').removeAttr('readonly');
				tr.find('select').removeAttr('disabled');
				btn.html('Lock');
				btnReset.removeAttr('disabled');
			}
		});
	}

	/**
	 * Add a row in the 'column' table with the data
	 * of an already existing column
	 */
	function addExistingCol(colName, col) {
		// Add html
		$('#vbuild-columns').append(`
			<tr column="${colName}" locked="true">
				<td><button role="toggle" class="btn btn-xs btn-danger">&times</button></td>
				<td><button role="reset" class="btn btn-xs btn-warning" disabled>Reset</button></td>
				<td><button role="edit" class="btn btn-xs btn-secondary" style="margin-left: 12px">Edit</button></td>
				<td><input class="form-control form-control-sm" type="text" name="name" value="${colName}" pattern="^\\S+$" title="Whitespace are not allowed" required readonly></td>
				<td><select class="form-control form-control-sm" type="text" name="type" required disabled></select></td>
				<td name="type-param"></td>
			</tr>
		`);

		let tr = $(`tr[column="${colName}"]`);

		// Commit changes on 'property name' input change
		tr.find('input[name="name"]').on('change', () => {
			updateChanges();
		});

		// Fill 'col type' select
		for (let type of COL_TYPES) {
			tr.find(`select[name="type"]`).append(`
				<option value="${type}">${type}</option>
			`);
		}

		// 'col type' select 'onchange', update 'type param' cell
		tr.find(`select[name="type"]`).on('change', () => {
			let type = tr.find(`select[name="type"]`).val();
			switch (type) {
				case 'date':
				case 'tinytext':
				case 'tinyint':
				case 'int':
					// No type param
					tr.find(`td[name="type-param"]`).html('');
					cols.delChange(colName, 'typeParam');
					break;

				case 'varchar':
					tr.find(`td[name="type-param"]`).html(`
						<input class="form-control form-control-sm" type="text" name="type-param" value="${col.typeParam ? col.typeParam : ''}" pattern="[0-9]{1,}" required data-container="body" data-toggle="popover" data-trigger="focus" data-placement="top" data-content="Number of characters.">
					`);
					break;

				case 'decimal':
					tr.find(`td[name="type-param"]`).html(`
						<div class="row">
							<div class="col-6 pr-1">
								<input class="form-control form-control-sm" type="text" name="type-param-precision" value="${col.typeParam[0] ? col.typeParam[0] : ''}" pattern="[0-9]{1,}" required data-container="body" data-toggle="popover" data-trigger="focus" data-placement="top" data-content="Number of significant digits.">
							</div>
							<div class="col-6 pl-1">
								<input class="form-control form-control-sm" type="text" name="type-param-scale" value="${col.typeParam[1] ? col.typeParam[1] : ''}" pattern="[0-9]{1,}" required data-container="body" data-toggle="popover" data-trigger="focus" data-placement="top" data-content="Number of digits that can be stored following the decimal point.">
							</div>
						</div>
					`);
					break;

				case 'enum':
					tr.find(`td[name="type-param"]`).html(`
						<input class="form-control form-control-sm" type="text" name="type-param-scale" value="${col.typeParam}" required data-container="body" data-toggle="popover" data-trigger="focus" data-placement="top" data-content="List of permitted values. Each entry is comma-separated. Whitespaces are allowed.">
					`);
					break;
			}

			// Commit changes on input change
			tr.find('td[name="type-param"]').find('input').on('change', () => {
				updateChanges();
			});

			// Trigger html pattern checking by refreshing the inputs
			let inputs = tr.find(`td[name="type-param"] input`);
			for (let i of inputs) {
				// Refresh the input
				i.value = i.value;
			}

			if (tr.attr('locked') == 'true') {
				tr.find(`td[name="type-param"] input`).attr('readonly', '');
			}

			// Enabling popover
			tr.find(`[data-toggle="popover"]`).popover();

		});

		// Select current col type
		tr.find(`select[name="type"]`).val(col.type);
		// Trigger 'on change' for initialization
		tr.find(`select[name="type"]`)[0].dispatchEvent(new Event('change'));

		// 'Delete column' button 'onclick' handler
		tr.find(`button[role="toggle"]`).on('click', () => {
			let btn = tr.find(`button[role="toggle"]`);
			if (tr.hasClass('deleted')) {
				tr.removeClass('deleted');
				btn.removeClass('btn-dark');
				btn.addClass('btn-danger');
				btn.html('&times');
			} else {
				tr.addClass('deleted');
				btn.removeClass('btn-danger');
				btn.addClass('btn-dark');
				btn.html('+');
			}
			updateChanges();
		});

		// 'Edit/Lock column' button 'onclick' handler
		tr.find(`button[role="edit"]`).on('click', () => {
			let btn = tr.find(`button[role="edit"]`);
			let btnReset = tr.find(`button[role="reset"]`);
			if (tr.attr('locked') == 'false') {
				tr.attr('locked', 'true');
				tr.find('input').attr('readonly', '');
				tr.find('select').attr('disabled', '');
				btn.html('Edit');
				btnReset.attr('disabled', '');
			} else {
				tr.attr('locked', 'false');
				tr.find('input').removeAttr('readonly');
				tr.find('select').removeAttr('disabled');
				btn.html('Lock');
				btnReset.removeAttr('disabled');
			}
		});

		// 'Reset column' button 'onclick' handler
		tr.find(`button[role="reset"]`).on('click', () => {
			cols.resetChanges(colName);
			tr.find('input[name="name"]').val(colName);
			tr.find('select[name="type"]').val(vTypes.opts[vTypes.val()][colName].type);
			tr.find('select[name="type"]')[0].dispatchEvent(new Event('change'));
		});
	}


	/**
	 * Get the value type of the specific form entries.
	 * Returns an object where each property is a visit type and each
	 * inner property is a column name with the column type
	 */
	function retrieveVisitTypes(study, callback) {
		vTypes.opts = {};
		$.ajax({
			url: '/visit_builder',
			type: 'post',
			dataType: 'json',
			data: {
				request: 'visitTypes',
				study: study
			},
			success: function(response) {
				let res = {};
				for (let vName in response) {
					v = response[vName];
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

						res[vName][col['COLUMN_NAME']] = {
							name: col['COLUMN_NAME'],
							type: colType,
							typeParam: (colParams !== undefined) ? colParams : ''
						}

						// Clean params
						switch (res[vName][col['COLUMN_NAME']].type) {
							case 'date':
							case 'int':
							case 'tinyint':
							case 'tinytext':
								res[vName][col['COLUMN_NAME']].typeParam = []
						}
					}
				}

				vTypes.opts = res;
				callback();
			}
		});
	}

	function retrieveStudies(callback) {
		$.ajax({
			url: '/visit_builder',
			type: 'post',
			dataType: 'json',
			data: {
				request: 'studies',
			},
			success: function(response) {
				callback(response);
			}
		});
	}

	function checkTableIsEmpty(study, visitType, callback) {
		$.ajax({
			url: '/visit_builder',
			type: 'post',
			dataType: 'json',
			data: {
				request: 'isDBTableEmpty',
				visitType: visitType,
				study: study
			},
			success: function(response) {

				callback(response.isEmpty);
			}
		});
	}

	/**
	 * Show a pop up that ask for confirmation
	 */
	function alertDiscardingChanges(callback) {
		alertify.confirm('Close?', 'Your changes will be lost.', () => {
			callback();
		}, () => {});
	}

	/**
	 * Check inputs, update and send the changes
	 */
	function commitChanges() {
		// Check input patterns
		let inputs = $('#vbuild-columns input');
		for (let i of inputs) {
			if (i.value == '') {
				alertifyWarning('Please, fill all the inputs.');
				throw 'Input is empty.';
			}
			if (!i.checkValidity()) {
				alertifyWarning('Inputs are not valid.');
				throw 'Inputs are not valid.';
			}
		}

		// Check 'type' select
		let selects = $('#vbuild-columns select');
		for (let s of selects) {
			if (!COL_TYPES.includes(s.value)) {
				alertifyWarning(`Type '${s.value}' is unknown.`);
				throw `Type '${s.value}' is unknown.`;
			}
		}

		updateChanges();
		sendChanges(() => {
			// Reset
			defaultStudy = study.val();
			defaultVType = vTypes.val();
			study.opts = {};
			vTypes.opts = {};
			cols.changes = {};
			cols.new = [];
			// Update study input
			study.dom.val(defaultStudy);
			study.dom[0].dispatchEvent(new Event('change'));
		});
	}

	/**
	 * Get all inputs value in the 'cols' object
	 */
	function updateChanges() {
		// Get new cols
		for (let nCol of cols.new) {
			let tr = $(`tr[column="${nCol.id}"]`);
			nCol.name = tr.find(`input[name="name"]`).val().replace(' ', '');
			nCol.type = tr.find(`select[name="type"]`).val();
			nCol.typeParam = [];
			let inputTypeParamDOM = tr.find(`td[name="type-param"] input`);
			for (let i = 0; i < inputTypeParamDOM.length; i++) {
				let params = inputTypeParamDOM[i].value.split(',');
				for (let p of params) {
					nCol.typeParam.push(p.trim());
				}
			}
		}

		// Get changed cols
		for (let colName in vTypes.opts[vTypes.val()]) {
			let col = vTypes.opts[vTypes.val()][colName];
			let tr = $(`tr[column="${colName}"]`);

			// Check if deleted
			if (tr.hasClass('deleted')) {
				cols.addChange(colName, 'isDeleted', true);
			} else {
				cols.delChange(colName, 'isDeleted');
			}

			// Check property name
			let inputName = tr.find(`input[name="name"]`).val().replace(' ', '');
			let originalName = col.name;
			if (inputName != originalName) {
				cols.addChange(colName, 'name', inputName);
			} else {
				cols.delChange(colName, 'name');
			}

			// Check type
			let inputType = tr.find(`select[name="type"]`).val();
			let originalType = col.type;
			if (inputType != originalType) {
				cols.addChange(colName, 'type', inputType);
			} else {
				cols.delChange(colName, 'type');
			}

			// Check type param
			let inputTypeParamDOM = tr.find(`td[name="type-param"] input`);
			if (inputTypeParamDOM.length !== 0) {
				let inputTypeParam = [];
				let originalTypeParam = col.typeParam;

				// Get all the params
				for (let i = 0; i < inputTypeParamDOM.length; i++) {
					// Split values where there are commas
					let params = inputTypeParamDOM[i].value.split(',');
					for (let p of params) {
						inputTypeParam.push(p.trim());
					}
				}

				// Check inputted params and orginal params
				let areEqual = true;
				if (inputTypeParam.length !== originalTypeParam.length) {
					areEqual = false;
				} else {
					for (let i = 0; i < originalTypeParam.length; i++) {
						if (originalTypeParam[i] != inputTypeParam[i]) {
							areEqual = false;
						}
					}
				}
				if (!areEqual) {
					cols.addChange(colName, 'typeParam', inputTypeParam);
				} else {
					cols.delChange(colName, 'typeParam');
				}
			} else {
				cols.delChange(colName, 'typeParam');
			}

		}
	}

	/**
	 * Send Ajax request for processing the visit in database
	 */
	function sendChanges(callback) {
		$.ajax({
			url: '/visit_builder',
			method: 'POST',
			data: {
				request: 'commitChanges',
				study: study.val(),
				visitType: vTypes.val(),
				changes: cols.changes,
				new: cols.new
			},
			complete: function(r) {
				try {
					r = JSON.parse(r.responseText);
					if (r.success == true) {
						alertifySuccess('Success');
						callback();
					} else {
						alertifyError('Server has encountered error:' + r.error);
					}
				} catch (e) {
					alertifyError('Unable to parse the server response: ' + e);
				}
			},
		})
	}
</script>