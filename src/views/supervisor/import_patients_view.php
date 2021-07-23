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

<script type="text/javascript">
	$(document).ready(function() {
		
		var $TABLE = $('#table');
		var $BTN = $('#export-btn');

		$('#sendJson').on('click', function() {
			let fr = new FileReader();
			fr.readAsText($("#fichier")[0].files[0]);
			fr.onload = () => {
				sendJson(JSON.stringify(JSON.parse(fr.result)));
			}
			
		});

		function sendJson(jsonText){
			$.ajax({
				type: "POST",
				url: '/import_patients',
				data: {json : jsonText}, // serializes the form's elements.
				success: function(data) {
					$("#documentation").html(data);
					$("#documentation").dialog('option', 'title', "Import Report");
					$("#documentation").dialog("open");

				}
			});
		}
		
		$('#parseJson').on('click', function() {

			// Read and parse imported JSON file
			const form = new FormData($("#patientJsonForm")[0]);
			let fr = new FileReader();
			fr.readAsText(form.get('fichier'));
			fr.onload = () => {
				// Drop the previous data in the table body
				$TABLE.find('tbody').empty();

				// Fill the table body with the JSON data
				const jsonData = JSON.parse(fr.result);
				for (let i = 0; i < jsonData.length; i++) {
					let patientData = jsonData[i];
					$TABLE.find('tbody').append('<tr><td contenteditable="true">' + patientData.patientNumber + '</td><td contenteditable="true">' + patientData.lastName + '</td><td contenteditable="true">' + patientData.firstName + '</td><td contenteditable="true">' + patientData.dateOfBirth + '</td><td contenteditable="true">' + patientData.gender + '</td><td contenteditable="true">' + patientData.registrationDate + '</td><td contenteditable="true">' + patientData.investigatorName + '</td><td contenteditable="true">' + patientData.investigatorNumCenter + '</td><td><span class="table-remove"><button type="button" class="btn btn-danger btn-rounded btn-sm my-0">Remove</button></span></td></tr>');
				}
			}
			
		});

		$('.table-add').click(function() {
			$TABLE.find('table').append('<tr><td contenteditable="true"><?=$study->patientCodePrefix?></td><td contenteditable="true">Last Name</td><td contenteditable="true">First Name</td><td contenteditable="true"><?= $importFormat?></td><td contenteditable="true">M/F</td><td contenteditable="true"><?= $importFormat?></td><td>Investigator Name</td><td>Investigator Center Number</td><td><span class="table-remove"><button type="button" class="btn btn-danger btn-rounded btn-sm my-0">Remove</button></span></td></tr>');
		});


		$TABLE.on('click', '.table-remove', function() {
			$(this).parents('tr').detach();
		});



		// A few jQuery helpers for exporting only
		jQuery.fn.pop = [].pop;
		jQuery.fn.shift = [].shift;

		$BTN.click(function() {
			var $rows = $TABLE.find('tr');
			var headers = [];
			var data = [];

			// Get the headers (add special header logic here)
			$($rows.shift()).find('th').each(function() {
				headers.push($(this).text());
			});

			// Turn all existing rows into a loopable array
			$rows.each(function() {
				var $td = $(this).find('td');
				var h = {};

				// Use the headers from earlier to name our hash keys
				headers.forEach(function(header, i) {
					h[header] = $td.eq(i).text();
				});

				data.push(h);
			});

			// send to server
			sendJson(JSON.stringify(data));
			
		});

	});
</script>
<div class="text-center">
    <h1>Import patients</h1>
    <form id="patientJsonForm">
    	<input type="file" id="fichier" name="fichier">
    	<input type="hidden" name="json" id="jsonData">
    	<button type="button" id="sendJson" class="btn btn-dark">Ok</button>
    	<button id="parseJson" type="button" class="btn btn-dark">Parse Json</button>
    </form>
	
	<div id="table" class="table-editable" style="overflow-x:auto">
		<span class="table-add float-right mb-3 mr-2"><button type="button" class="btn btn-success btn-rounded btn-sm my-0">Add</button>
		</span>

		<table class="table table-striped">
			<thead>
				<tr>
					<th class="text-center">patientNumber</th>
					<th class="text-center">lastName</th>
					<th class="text-center">firstName</th>
					<th class="text-center">dateOfBirth</th>
					<th class="text-center">gender</th>
					<th class="text-center">registrationDate</th>
					<th class="text-center">investigatorName</th>
					<th class="text-center">investigatorNumCenter</th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td class="pt-3-half" contenteditable="true">Patient Number</td>
					<td class="pt-3-half" contenteditable="true">Last Name</td>
					<td class="pt-3-half" contenteditable="true">First Name</td>
					<td class="pt-3-half" contenteditable="true"><?= $importFormat?></td>
					<td class="pt-3-half" contenteditable="true">M/F</td>
					<td class="pt-3-half" contenteditable="true"><?= $importFormat?></td>
					<td class="pt-3-half" contenteditable="true">Investigator Name</td>
					<td class="pt-3-half" contenteditable="true">Investigator Center Number</td>
					<td>
						<span class="table-remove"><button type="button" class="btn btn-danger btn-rounded btn-sm my-0">Remove</button></span>
					</td>
				</tr>
			</tbody>
			<!-- This is our clonable table line -->

		</table>
		<button id="export-btn" class="btn btn-primary">Add Patient List</button>
	</div>
</div>