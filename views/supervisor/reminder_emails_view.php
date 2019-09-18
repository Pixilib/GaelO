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

<script type="text/javascript">

    tinymce.init({
        selector: '#myTextarea',
	});

    $(document).ready(function () {
        
       	$('.rolesRadio').change(function(){
  	       	
  	       	if ($('#investigator')[0].checked){
  	       		$('.job').prop('disabled', false);
  	       	}else{
  	       		$('.job').prop('disabled', true);
  	       	}
  	       	
       	});

       	$('#validateSendEmails').on('click', function(){
       		tinyMCE.triggerSave();
       		alertifyWarning('Sending Emails');
			$.ajax({
                   type: "POST",
                   dataType: 'json',
                   url: '/reminder_emails',
                   data: $("#reminderEmailsForm").serialize()+"&validate=1", // serializes the form's elements.
                   success: function(data)
                   {
                        if(data['status']=="Success"){
                        	alertifySuccess('Sent mails to '+data['centerNb']+' centers');
                        }else{
                        	alertifyError('error during send');
                        }
                   }
            });	
       	});
       	
    });
       
    </script>

<form class="col align-self-center" id="reminderEmailsForm">
	<label>Send following e-mails reminders to the Investigational Centers</label>
	<br>
	<br>

	<div class="col">
		<input type="radio" id="upload" name="radioForm" value="upload"
			checked> <label for="upload">Upload reminders</label>
	</div>

	<div class="col">
		<input type="radio" id="investigation" name="radioForm"
			value="investigation"> <label for="investigation">Investigation form
			reminders</label>
	</div>

	<div class="col">
		<input type="radio" id="corrective" name="radioForm"
			value="corrective"> <label for="corrective">Corrective actions
			reminders</label>
	</div>
	<br> <label>Send the reminders to following profiles: </label> <br>

	<div class="col">
		<input class="rolesRadio" type="radio" id="investigator"
			name="radioFormRoles" value="investigator" checked> <label
			for="investigator">Investigator</label>
	</div>

	<div class="col">
		<input class="job" type="checkbox" id="cra" name="cra" value="1"> <label
			for="cra">CRA</label>
	</div>
	<div class="col">
		<input class="job" type="checkbox" id="nurse" name="nurse" value="1">
		<label for="nurse">Study nurses</label>
	</div>
	<div class="col">
		<input class="job" type="checkbox" id="nuclearist" name="nuclearist"
			value="1"> <label for="nuclearist">Nuclearists</label>
	</div>
	<div class="col">
		<input class="job" type="checkbox" id="radiologist" name="radiologist"
			value="1"> <label for="radiologist">Radiologist</label>
	</div>
	<div class="col">
		<input class="job" type="checkbox" id="pi" name="pi" value="1"> <label
			for="pi">PI</label>
	</div>

	<div class="col">
		<input class="rolesRadio" type="radio" id="supervisor"
			name="radioFormRoles" value="supervisor"> <label for="supervisor">Supervisor</label>
	</div>

	<div class="col">
		<input class="rolesRadio" type="radio" id="monitor"
			name="radioFormRoles" value="monitor"> <label for="monitors">Monitors</label>
	</div>
	<br> <label>Title: </label> <input class="form-control" type="text"
		id="title" name="title" value="Missing Uploads"> <br>

	<textarea id="myTextarea" name="userText"></textarea>

	<div class="text-center">
		<button class="text-center btn btn-dark" type="button" name="validate" 
			id="validateSendEmails">Validate</button>
	</div>
</form>