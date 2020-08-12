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

		//Reload this page in case of click on "main" button
		$("#btn_main").on("click", function() {
			$("#mainDiv").load('/root_supervisor', {
				etude: '<?=$_SESSION['study']?>',
				role: '<?=$_SESSION['role']?>'
			});
		});

		//Load the upload manager when click on "upload manager"
		$("#btn_upload_manager").on("click", function() {
			$("#supervisorDiv").load('/upload_manager');
		});

		//Load the review manager when click on "review manager button"
		$("#btn_review_manager").on("click", function() {
			$("#supervisorDiv").load('/review_manager');
		});

		//Load the Tracker when click on Tracker button
		$("#dropdown_tracker .dropdown-item").on("click", function() {
			var updatedString = this.id.replace("Logs", "");
			$("#supervisorDiv").load('/tracker',{
				role : updatedString
			});
		});

		//Load the Statistics when click on Statistics button
		$("#dropdown_statistics .dropdown-item").on("click", function() {
			$("#supervisorDiv").load('/statistics',{
				chartId: this.id
			});
		});

		//Load the download manager when click on "download manager button"
		$("#btndownloadmanager").on("click", function() {
			$("#supervisorDiv").load('/download_manager');
		});

		//Load the import patients
		$("#btn_import_patients").on("click", function() {
			$("#supervisorDiv").load('/import_patients');
		});

		//Load the user details
		$("#btn_users_details").on("click", function() {
			$("#supervisorDiv").load('/users_details');
		});

		$("#btnexport").on("click", function() {
			location.href = 'scripts/export_study_data.php';

		});

		//Prepare the documentation dialog
		$("#documentation").dialog({
			autoOpen: false,
			width: 'auto',
			height: 'auto',
			modal: true,
			position: {
				my: "center",
				at: "center",
				of: window
			},
			closeOnEscape: false
		});

		//Open the documentation dialog when click on "set documentation"
		$("#setdocumentationButton").on("click", function() {
			$("#documentation").load('/documentation_supervisor');
			$("#documentation").dialog('option', 'title', "Documentation of study : <?=$_SESSION['study']?>");
			$("#documentation").dialog("open");
		});

		$('#userRoles').DataTable({
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
			"bSortCellsTop": true,
			"iDisplayLength": 5
		});

		//Build the datatable dysplaying each visit status
		$('#tableau').DataTable({
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
			//Retrive data from internal method
			data: <?=make_Json($studyObject)?>,
			columns: [{
					data: 'center'
				},
				{
					data: 'code'
				},
				{
					data: 'withdraw'
				},
				{
					data: 'visit_modality'
				},
				{
					data: 'visit_type'
				},
				{
					data: 'status_done'
				},
				{
					data: 'upload_status'
				},
				{
					data: 'state_investigator_form'
				},
				{
					data: 'state_quality_control'
				},
				{
					data: 'review'
				}
			],
			"columnDefs": [{
					"title": "Center",
					"targets": 0
				},
				{
					"title": "Patient Number",
					"targets": 1
				},
				{
					"title": "Patient Status",
					"targets": 2
				},
				{
					"title": "Visit Modality",
					"targets": 3
				},
				{
					"title": "Visit Name",
					"targets": 4
				},
				{
					"title": "Visit Status",
					"targets": 5
				},
				{
					"title": "Series upload",
					"targets": 6
				},
				{
					"title": "Investigation Form",
					"targets": 7
				},
				{
					"title": "Quality Control",
					"targets": 8
				},
				{
					"title": "Review",
					"targets": 9
				}
			],
			"bSortCellsTop": true
		});

		// Apply the search
		$('#visitStatusDiv').on('change', ".column_search", function() {
			let searchValue = this.value
			let regex = false

			if($(this).prop("class").includes('select_search') && this.value != ""){
				searchValue = "^"+this.value+"$"
				regex = true
			}

			$('#tableau').DataTable()
				.column($(this).parent().index())
				.search(searchValue, regex)
				.draw();
		});

		$('#table_patient_informations').DataTable({
			"sDom": 'Blrtip',
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
			"bSortCellsTop": true,
			"scrollX": true
		});


		$('#patientInformation').on('change', ".column_search", function() {
			let searchValue = this.value
			let regex = false

			if($(this).prop("class").includes('select_search') && this.value != ""){
				searchValue = "^"+this.value+"$"
				regex = true
			}

			$('#table_patient_informations').DataTable()
				.column($(this).parent().index())
				.search(this.value)
				.draw();
		});


	});

	function linkVisitInfos(id) {
		$("#supervisorDiv").load('/visit_infos', {
			id_visit: id
		});
	};

	function linkPatientInfos(code) {
		$("#supervisorDiv").load('/patient_infos', {
			patient_num: code
		});
	};
</script>

<!-- Button menu for supervisor functions -->
<div class="text-center">
	<input id="btn_main" class="btn btn-primary" type="button" value="Main">
	<input id="btn_upload_manager" class="btn btn-primary" type="button" value="Upload Manager">
	<input id="btn_review_manager" class="btn btn-primary" type="button" value="Review Manager">
	<div id="dropdown_tracker" style="display: inline-block">
		<input id="btn_tracker" class="btn btn-primary dropdown-toggle" type="button" value="Tracker" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
		<div class="dropdown-menu" aria-labelledby="btn_tracker">
			<a class="dropdown-item" href="#" id="investigatorLogs">Investigators</a>
			<a class="dropdown-item" href="#" id="controllerLogs">Controllers</a>
			<a class="dropdown-item" href="#" id="reviewerLogs">Reviewers</a>
			<a class="dropdown-item" href="#" id="supervisorLogs">Supervisors</a>
			<a class="dropdown-item" href="#" id="messageLogs">Messages</a>
		</div>
	</div>

	<div id="dropdown_statistics" style="display: inline-block">
		<input id="btn_statistics"  class="btn btn-primary dropdown-toggle" type="button" value="Statistics" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
		<div class="dropdown-menu" aria-labelledby="btn_statistics">
			<a class="dropdown-item" href="#" id="acquPETDelay">Acquisition PET Delay</a>
			<a class="dropdown-item" href="#" id="studyProgress">Study Progress</a>
			<a class="dropdown-item" href="#" id="reviewCount">Review Count</a>
			<a class="dropdown-item" href="#" id="reviewData">Review Data</a>
			<a class="dropdown-item" href="#" id="reviewStatus">Review Status & Conclusion</a>
			<a class="dropdown-item" href="#" id="QCStatus">QC Status</a>
			<a class="dropdown-item" href="#" id="QCTime">QC Time</a>
			<a class="dropdown-item" href="#" id="conclusionTime">Conclusion Time</a>
		</div>
	</div>
	
	<input id="btndownloadmanager" class="btn btn-primary" type="button" value="Download Dicoms">
	<input id="btn_import_patients" class="btn btn-primary" type="button" value="Import Patients">
	<input id="btn_users_details" class="btn btn-primary" type="button" value="Users Details">
	<input id="btnexport" class="btn btn-primary" type="button" value="Export">
	<input id="setdocumentationButton" class="btn btn-primary" type="button" value="Set Documentation">
</div><br>

<!-- Table to show visit status in datatable -->
<div id="supervisorDiv">

	<h1>Patients Status</h1>
	<!--Add the "Patient Information" table -->
	<div id="patientInformation" class="upManagerDiv">
		<table id="table_patient_informations" class="table table-striped" style="text-align:center; width:100%">
			<thead>
				<tr>
					<th>Center</th>
					<th>Patient Number</th>
					<th>Initials</th>
					<th>Gender</th>
					<th>Birthdate</th>
					<th>Registration date</th>
					<th>Patient status</th>
					<th>Withdrawal date</th>
					<th>Withdrawal reason</th>
				</tr>
				<tr>
					<th><input type="text" placeholder="Search" class="column_search" style="max-width:75px" /></th>
					<th><input type="text" placeholder="Search" class="column_search" style="max-width:75px" /></th>
					<th><input type="text" placeholder="Search" class="column_search" style="max-width:75px" /></th>
					<th><input type="text" placeholder="Search" class="column_search" style="max-width:75px" /> </th>
					<th><input type="text" placeholder="Search" class="column_search" style="max-width:75px" /> </th>
					<th><input type="text" placeholder="Search" class="column_search" style="max-width:75px" /> </th>
					<th>
						<select type="text" placeholder="Search" class="column_search" style="max-width:75px" >
							<option value="">Choose</option>	
							<option value="Included">Included</option>
							<option value="Withdrawn">Withdrawn</option>
						</select> 			
					</th>
					<th><input type="text" placeholder="Search" class="column_search" style="max-width:75px" /> </th>
					<th><input type="text" placeholder="Search" class="column_search" style="max-width:75px" /> </th>
				</tr>
			</thead>
			<tbody>
				<?php
				//Get all patients in the current study
				$allPatientsInStudy=$studyObject->getAllPatientsInStudy();

				foreach ($allPatientsInStudy as $patient) {
					?>
					<tr>
						<td><?=$patient->patientCenter?></td>
						<td><?="<a onclick='linkPatientInfos(".$patient->patientCode.")' href='javascript:void(0);'>".$patient->patientCode."</a>"?></td>
						<td><?=$patient->patientLastName.''.$patient->patientFirstName?></td>
						<td><?=$patient->patientGender?></td>
						<td><?=$patient->patientBirthDate?></td>
						<td><?=$patient->patientRegistrationDate?></td>
						<td><?php if ($patient->patientWithdraw) echo ("Withdrawn");
								else echo ("Included") ?></td>
						<td><?=$patient->patientWithdrawDateString?></td>
						<td><?=htmlspecialchars($patient->patientWithdrawReason)?></td>
					</tr>
				<?php
			}
			?>
			</tbody>
		</table>
	</div>

	<h1>Visits Status</h1>
	<div id="visitStatusDiv">
    	<table class="table table-striped" id="tableau" style="text-align:center; width:100%">
    		<thead>
    			<!-- Header for column name (filled by datatable) -->
    			<tr>
    				<th></th>
					<th></th>
					<th></th>
    				<th></th>
    				<th></th>
    				<th></th>
    				<th></th>
    				<th></th>
    				<th></th>
    				<th></th>
    			</tr>
    			<!-- search inputs -->
    			<tr>
    				<th><input type="text" placeholder="Search" class="column_search" style="max-width:75px" /></th>
    				<th><input type="text" placeholder="Search" class="column_search" style="max-width:75px" /></th>
    				<th><input type="text" placeholder="Search" class="column_search" style="max-width:75px" /></th>
    				<th><input type="text" placeholder="Search" class="column_search" style="max-width:75px" /></th>
    				<th><input type="text" placeholder="Search" class="column_search" style="max-width:75px" /></th>
    				<th>
						<select type="text" placeholder="Search" class="column_search select_search" style="max-width:75px" >
							<option value="">Choose</option>	
							<option value="<?=Visit::DONE?>"> <?=Visit::DONE?> </option>
							<option value="<?=Visit::NOT_DONE?>"> <?=Visit::NOT_DONE?> </option>
						</select>
					</th>
    				<th>
						<select type="text" placeholder="Search" class="column_search" style="max-width:75px" >
							<option value="">Choose</option>	
							<option value="<?=Visit::DONE?>" > <?=Visit::DONE?></option>
							<option value="<?=Visit::UPLOAD_PROCESSING?>"> <?=Visit::UPLOAD_PROCESSING?> </option>
							<option value="<?=Visit::NOT_DONE?>"> <?=Visit::NOT_DONE?> </option>
						</select>
					</th>
    				<th>
						<select type="text" placeholder="Search" class="column_search" style="max-width:75px" >
							<option value="">Choose</option>	
							<option value="<?=Visit::LOCAL_FORM_NOT_DONE?>"> <?=Visit::LOCAL_FORM_NOT_DONE?> </option>
							<option value="<?=Visit::LOCAL_FORM_DRAFT?>"> <?=Visit::LOCAL_FORM_DRAFT?> </option>
							<option value="<?=Visit::LOCAL_FORM_DONE?>"> <?=Visit::LOCAL_FORM_DONE?> </option>
						</select> 
					</th>
    				<th>
						<select type="text" placeholder="Search" class="column_search" style="max-width:75px" >
							<option value="">Choose</option>
							<option value="<?=Visit::QC_NOT_DONE?>"> <?=Visit::QC_NOT_DONE?> </option>
							<option value="<?=Visit::QC_CORRECTIVE_ACTION_ASKED?>"> <?=Visit::QC_CORRECTIVE_ACTION_ASKED?> </option>
							<option value="<?=Visit::QC_WAIT_DEFINITVE_CONCLUSION?>"> <?=Visit::QC_WAIT_DEFINITVE_CONCLUSION?> </option>
							<option value="<?=Visit::QC_ACCEPTED?>"> <?=Visit::QC_ACCEPTED?> </option>
							<option value="<?=Visit::QC_REFUSED?>"> <?=Visit::QC_REFUSED?> </option>	
						</select> 
					</th>
    				<th>
						<select type="text" placeholder="Search" class="column_search" style="max-width:75px" >
							<option value="">Choose</option>	
							<option value="<?=Visit::REVIEW_NOT_DONE?>" > <?=Visit::REVIEW_NOT_DONE?> </option>
							<option value="<?=Visit::REVIEW_ONGOING?>" > <?=Visit::REVIEW_ONGOING?> </option>
							<option value="<?=Visit::REVIEW_WAIT_ADJUDICATION?>" > <?=Visit::REVIEW_WAIT_ADJUDICATION?> </option>
							<option value="<?=Visit::REVIEW_DONE?>" > <?=Visit::REVIEW_DONE?> </option>
						</select> 		
					</th>
    			</tr>
    		</thead>
    	</table>
	</div>
	<div id="documentation"> </div>

</div>
