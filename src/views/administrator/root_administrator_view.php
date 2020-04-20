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
	$(document).ready( function () {

		//Admin Dialog for Add / Modify User / create and Modify center
		$("#adminDialog").dialog({
			autoOpen: false,
			width : 'auto',
			height : 'auto',
			modal : true,
			position: { my: "center", at: "center", of: window },
			closeOnEscape: false,
			beforeClose: function(event, ui){
				if(event.originalEvent !== undefined){
					//Closed manually
					alertify.confirm('Close?','Your changes will be lost', function(){ $("#adminDialog").dialog('close'); }, function(){});
			        return false;
				}else{
					return true;
				}
		        
			}

		});

		//Open the modify center dialog on click
		$('#openModifyCenter').on('click',function () {
			$( "#adminDialog" ).load('/modify_centers', function(){
				$("#adminDialog").dialog('option', 'title', 'Modify Center');
				$( "#adminDialog" ).dialog('open');
			});

		});

		//Open the create study dialog on click
		$('#openCreateStudy').on('click',function() {
			$( "#adminDialog" ).load('/create_study', function(){
				$("#adminDialog").dialog('option', 'title', 'Create Study');
				$( "#adminDialog" ).dialog('open');
			});
			
		});

		//Open the study activation interface on click
		$('#openStudyActivation').on('click',function() {
			$( "#adminDialog" ).load('/study_activation', function(){
				$("#adminDialog").dialog('option', 'title', 'Study Activation');
				$( "#adminDialog" ).dialog('open');
			});
			
		});
		
		
		//Load the dynamic table on click on the study selector
		$('#confirmSelect').on('click', function (){
			$("#userTable").load('/user_table',{
				 study : $( "#studySelector" ).val()
			});

		});

		//Load the log on click
		$('#adminLogs').on('click', function (){
			$("#adminDisplay").load('/tracker_admin');

		});

		$('#usersLogs').on('click', function (){
			$("#adminDisplay").load('/tracker_user');
		});
		
		//Load visit builder in dialog
		$('#defineVisit').on('click', function (){
			$( "#adminDisplay" ).load('/visit_builder');
		});
		
		//Load the new user dialog
		$('#addUser').on('click', function openNewUser() {
			$( "#adminDialog" ).load('/new_user', function() {
				$("#adminDialog").dialog('option', 'title', 'Add User');
				$( "#adminDialog" ).dialog('open');
			});
			
		});

		//Load the plateform preference dialog
		$('#prefs').on('click',function() {
			$( "#adminDialog" ).load('/preferences', function(){
				$("#adminDialog").dialog('option', 'title', 'Platform Preferences');
				$( "#adminDialog" ).dialog('open');
			});
			
		});

		
	});


</script>

<div class="text-center">

	<h1>Administration Panel</h1>

	<!-- Button Panel for Admin functions -->
	<div>
		<input class="btn btn-primary" type="button" value="Add User"
			id="addUser"> 
		<input class="btn btn-primary" type="button"
			value="Modify center list" id="openModifyCenter"> 
		<input
			class="btn btn-primary" type="button" value="Create Study"
			id="openCreateStudy"> 
		<input class="btn btn-primary" type="button"
			value="Define Visit" id="defineVisit"> 
		<input
			class="btn btn-primary" type="button" value="Study Activation"
			id="openStudyActivation"> 
		<input class="btn btn-primary"
			type="button" value="Prefs" id="prefs"> 
			
		<input type="button" class="btn btn-primary dropdown-toggle" value="Logs" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
		<div class="dropdown-menu">
			<a class="dropdown-item" href="#" id="adminLogs">Admin Logs</a>
			<a class="dropdown-item" href="#" id="usersLogs">User Logs</a>
		</div>
		
		<a
			href="/export_database"> 
			<input
			class="btn btn-primary" type="button" value="Export DB"
			id="exportDb">
		</a>
	</div>
	<br>



</div>
<br>

<div id="adminDialog"></div>
<div id="adminDisplay">
	<!------------ Study Selector ----------------------->
	<div class="text-center">
		<label>User account database of study:</label> <SELECT
			class="custom-select" name="Study" id="studySelector"
			style="width: auto;">
			<?php
			// Generate the study selector, starting with "All Studies" and then all availables studies
			echo '<option value="All Studies">All Studies</option>';
			
			foreach ($etudes as $etude) {
				echo '<option value="'.htmlspecialchars($etude).'">'.htmlspecialchars($etude).'</option>';
			}
			
			?>
		</SELECT>
		<button id="confirmSelect" class="btn btn-primary">Confirm</button>
	</div>
	<div id = "userTable"></div>

</div>

