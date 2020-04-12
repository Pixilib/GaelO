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

      $(document).ready(function() {
        //Listener for administrator privilege warning
        $('#administrator').click(function(e) {
        	if($("#administrator").is(':checked')){
        		alertifyWarning('Administrator privileges will be set');
        	}
		});
        //Add Chosen to selectors
        $("#selecteur_centre").chosen();
        $("#center").chosen();
        $("#one").chosen();

        $("#confirmer").on('click', function(){
			$('#profile option').prop('selected', true);
			$('#afficheur_centre option').prop('selected', true);
			//Send form in Ajax
			$.ajax({
	           type: "POST",
	           dataType: 'json',
	           url: '/new_user',
	           data: $("#formulaire").serialize()+"&confirmer=1", // serializes the form's elements.
	           success: function(data) {
                    if (data != "Success") {
                      alertifyError(data);
                      return;
                    }else{
                        //Close the create user dialog
                        $("#adminDialog").dialog('close');
                        alertifySuccess('User Created');
                        //Refresh the user table
                        $("#studyTable").load('/user_table',{
                        	 study : $( "#studySelector" ).val()
                        });
                    }
                    
				}
			});
		});

      });

      //Switch option item from origin to destination
      function bascule(origin, destination){
    	  var value=$("#"+origin+" option:selected").val();
    	  var text=$("#"+origin+" option:selected").text();

    	  if ( $('#'+destination+' option:contains("'+text+'")').length == 0){
    		 $('#'+destination+'').append('<option value="'+value+'">'+text+'</option>');
       	  }else{
       		alertifyError("Already in list");
       	  }

        }

      //Remove one option
       function del_list(list){
    	  $("#"+list+" option:selected").remove();
       }
       
</script>
<div class="container jumbotron">
	<form name="formulaire" id="formulaire">
		<div class="form-group row">
			<label class="col-form-label">Username*:</label>
			<div class="col-sm-10">
				<input type="text" class="form-control" id="username"
					name="username" placeholder="Username" maxlength="32" required>
			</div>
		</div>

		<div class="form-group row">
			<label class="col-form-label">Last name*:</label>
			<div class="col-sm-10">
				<input type="text" class="form-control" id="last_name"
					name="last_name" placeholder="Last name" maxlength="32" required>
			</div>
		</div>

		<div class="form-group row">
			<label class="col-form-label">First name*:</label>
			<div class="col-sm-10">
				<input type="text" class="form-control" id="first_name"
					name="first_name" placeholder="First name" maxlength="32" required>
			</div>
		</div>

		<div class="form-group row">
			<label class="col-form-label">Email*:</label>
			<div class="col-sm-10">
				<input type="email" class="form-control" id="email" name="email"
					placeholder="mail@exemple.fr" maxlength="255" required>
			</div>
		</div>

		<div class="form-group row">
			<label class="col-form-label">Phone number:</label>
			<div class="col-sm-10">
				<input type="tel" class="form-control" maxlength="32" name="phone">
			</div>
		</div>

		<div class="form-group row">
			<div class="form-group">
				<label class="col-form-label">Affected profiles*:</label>
			</div>
			<div class="form-group col text-center">
				<!------------------------ Roles selected ----------------------->
				<SELECT id="profile" name="profile[]" class="form-control" multiple>

				</SELECT> <input onclick="del_list('profile')" class="btn btn-dark"
					type="button" value="-">
			</div>
			<div class="form-group col text-center">
				<!------------------------- Roles selector ----------------------->
				<SELECT id="one" class="custom-select" name="role">
				<?php
				$allRoles=array(
					User::INVESTIGATOR,
					User::MONITOR,
					User::CONTROLLER,
					User::SUPERVISOR,
					User::REVIEWER
				);
				foreach ($availableStudies as $availableStudy) {
					foreach ($allRoles as $roleValue) {
						echo '<option value="'.$roleValue.'@'.htmlspecialchars($availableStudy).'">['.htmlspecialchars($availableStudy).'] - ['.$roleValue.']</option>';
					}
				}
				?>
				</SELECT> <input onclick="bascule('one','profile')"
					class="btn btn-dark" type="button" value="+">
			</div>
		</div>

		<div class="form-group row">
			<label class="col-form-label">Administrator :</label> <input
				class="form-group col" type="checkbox" name="administrator"
				value="1" id="administrator">
		</div>


		<div class="form-group row">
			<label class="col-form-label">Job:</label>
			<div class="col-sm-10">
				<!-------------------- Job Selector ----------------------->
				<SELECT class="custom-select" name="job">
                <?php
				// Add all available jobs
				foreach ($jobs as $job) {
					echo '<option value="'.$job.'">'.$job.'</option>';
				}
				?>
              </SELECT>
			</div>
		</div>


		<div class="form-group row">
			<label class="col-form-label">Investigational center*:</label>
			<!------------------------ Main Center Selector ----------------------->
			<SELECT class="custom-select" name="center" id="center" required>
              <?php
			// Add all possible centers
			foreach ($centers as $center) {
				echo '<option value="'.$center->code.'">['.$center->code.'] - ['.htmlspecialchars($center->name).']</option>';
			}
			?>
            </SELECT>

		</div>

		<div class="form-group row">
			<div class="form-group">
				<label class="col-form-label">Affiliated centers:</label>
			</div>
			<div class="form-group col text-center">
				<!------------------------ Selected Centers ----------------------->
				<SELECT name="afficheur_centre[]" id="afficheur_centre"
					class="custom-select" multiple></SELECT> <input
					onclick="del_list('afficheur_centre')" class="btn btn-dark"
					type="button" value="-">
			</div>
			<div class="form-group col text-center">
				<!-------------------------- Center Selector ----------------------->
				<SELECT id="selecteur_centre" name="selecteur_centre"
					class="custom-select">
				<?php
				// Add all possible centers
				foreach ($centers as $center) {
					echo '<option value="'.$center->code.'">['.$center->code.'] - ['.htmlspecialchars($center->name).']</option>';
				}
				?>
				</SELECT> <input
					onclick="bascule('selecteur_centre','afficheur_centre')"
					class="btn btn-dark" type="button" value="+">
			</div>
		</div>
		
		<div class="form-group row">
			<label class="col-form-label">Orthanc Address :</label> <input
				class="form-group col" type="text" name="orthancAddress" placeholder="http://address:port" maxlength="255">
		</div>
		
		<div class="form-group row">
			<label class="col-form-label">Orthanc Login :</label> <input
				class="form-group col" type="text" name="orthancLogin" maxlength="255">
		</div>
		
		<div class="form-group row">
			<label class="col-form-label">Orthanc Password :</label> <input
				class="form-group col" type="password" name="orthancPassword" maxlength="255">
		</div>

		<div class="text-center container">
			<button name="confirmer" type="button" id="confirmer"
				class="btn btn-dark">Add user</button>
		</div>

	</form>

</div>