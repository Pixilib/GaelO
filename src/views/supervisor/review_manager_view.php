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

<div id="reviewTableDiv">
	<h1>Review Details :</h1>
	<br>
	<div>
		<form id="sendReviewersEmails">
			<input type="hidden" name="reviewMap" value="<?= json_encode($reviewdetailsMap) ?>"> <input type="button" class="btn btn-primary" id="btnReviewerReminders" value="Send Reminder Emails">
		</form>
	</div>
	<table id="reviewTable" class="table table-striped" style="width: 100%">
		<thead>
			<!-- Header column filled by title by datatable -->
			<tr>
				<th></th>
				<th></th>
				<th></th>
				<th></th>
				<th></th>
				<th></th>
				<th></th>
				<th></th>
			</tr>
			<!-- Search raw -->
			<tr>
				<th><input type="text" placeholder="Search" class="column_search" style="max-width: 75px" /></th>
				<th><input type="text" placeholder="Search" class="column_search" style="max-width: 75px" /></th>
				<th><input type="text" placeholder="Search" class="column_search" style="max-width: 75px" /></th>
				<th><input type="text" placeholder="Search" class="column_search" style="max-width: 75px" /></th>
				<th><input type="text" placeholder="Search" class="column_search" style="max-width: 75px" /></th>
				<th><input type="text" placeholder="Search" class="column_search" style="max-width: 75px" /></th>
				<th><input type="text" placeholder="Search" class="column_search" style="max-width: 75px" /></th>
				<th><input type="text" placeholder="Search" class="column_search" style="max-width: 75px" /></th>
			</tr>
		</thead>
	</table>
</div>

<script type="text/javascript">
$(document).ready(function () {
	//DataTable for visit by visit details
	let reviewTable=$('#reviewTable').DataTable({
		"sDom": 'Blrtip',
		"scrollX": true,
		buttons: [{
			extend: 'collection',
			text: 'Export',
			buttons: [
					{
						extend: 'copy',
						exportOptions: {
							modifier : {
								order : 'index', // 'current', 'applied','index', 'original'
								page : 'all', // 'all', 'current'
								search : 'applied' // 'none', 'applied', 'removed'
							}
						}
					},
					{
						extend: 'excel',
						exportOptions: {
							modifier : {
								order : 'index', // 'current', 'applied','index', 'original'
								page : 'all', // 'all', 'current'
								search : 'applied' // 'none', 'applied', 'removed'
							}
						}
					},
					{
						extend: 'csv',
						exportOptions: {
							modifier : {
								order : 'index', // 'current', 'applied','index', 'original'
								page : 'all', // 'all', 'current'
								search : 'applied' // 'none', 'applied', 'removed'
							}
						}
					},
					{
						extend: 'pdf',
						exportOptions: {
							modifier : {
								order : 'index', // 'current', 'applied','index', 'original'
								page : 'all', // 'all', 'current'
								search : 'applied' // 'none', 'applied', 'removed'
							}
						}
					},
					{
						extend: 'print',
						exportOptions: {
							modifier : {
								order : 'index', // 'current', 'applied','index', 'original'
								page : 'all', // 'all', 'current'
								search : 'applied' // 'none', 'applied', 'removed'
							}
						}
					}
					]
		}],
		data: <?=generateJSONforDatatable($reviewdetailsMap)?>,
		columns: [
			{ data: 'patientNumber' },
			{ data: 'visitModality' },
			{ data: 'visit' },
			{ data: 'acquisitionDate' },
			{ data: 'numberOfReview' },
			{ data: 'reviewStatus' },
			{ data: 'reviewDoneBy' },
			{ data: 'reviewNotDoneBy' },
		],
		"columnDefs": [
			{ "title": "Patient Number", "targets": 0 },
			{ "title": "Visit Modality", "targets": 1 },
			{ "title": "Visit", "targets": 2 },
			{ "title": "Acquisition Date", "targets": 3 },
			{ "title": "Number Of Review", "targets": 4 },
			{ "title": "Review Status", "targets": 5 },
			{ "title": "Review Done By", "targets": 6 },
			{ "title": "Review Not Done By", "targets": 7 },
		],
		"bSortCellsTop": true
	});

	// Search function on datatable
	$('#reviewTableDiv').on('keyup', ".column_search", function() {
		reviewTable.DataTable()
			.column($(this).parent().index())
			.search(this.value)
			.draw();
	});

	//Action to send reminders of missing reviews to reviewers
	//SK A REVOIR L EMAILER
	$('#btnReviewerReminders').on('click', function(e) {
		var confirmation = confirm('You are about to send reviewers reminders, do you confirm your action?');
		if (confirmation) {
			$.ajax({
				type: "POST",
				dataType: 'json',
				url: "scripts/reminder_review_emails.php",
				data: $("#sendReviewersEmails").serialize(), // serializes the form's elements.
				success: function(data) {
					if (data['status'] == "Success") {
						alertifySuccess('Reminders sent for ' + data['nbReviewEmailed'] + ' missing reviews and ' + data['nbReviewersEmailed'] + ' reviewers');
					} else {
						alertifyError('No Permission');
					}

				},
				error: function(jqXHR, textStatus, errorThrown) {
					alertifyError('failure');
				}
			});
		}
	});
});
</script>