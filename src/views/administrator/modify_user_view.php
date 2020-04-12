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
	//Transfert an option to a selector to another
    function bascule(origin, destination){
	  var value=$("#"+origin+" option:selected").val();
	  var text=$("#"+origin+" option:selected").text();

	  if ( $('#'+destination+' option:contains("'+text+'")').length == 0){
		 $('#'+destination+'').append('<option value="'+value+'">'+text+'</option>');
   	  }else{
   		alertifyError("Already in list");
   	  }

    }

    //Delete a select option
    function del_list(list){
	  $("#"+list+" option:selected").remove();
    }

	$(document).ready(function() {

        $('#administrator').click(function(e) {
        	if($("#administrator").is(':checked')){
        		alertifyWarning('Administrator privileges will be set');
        	}
        });
      
        //Add Chooser for selectors
        $("#selecteur_centre").chosen();
        $("#main_center").chosen();
        $("#one").chosen();

		//Send form in Ajax
		$("#confirmerModify").on('click', function(){
          	//Select all options of the add items
			$('#profile option').prop('selected', true);
			$('#afficheur_centre option').prop('selected', true);
			
			//Send form in Ajax
			$.ajax({
	           type: "POST",
	           dataType: 'json',
	           url: '/modify_user',
	           data: $("#formulaire").serialize()+"&confirmer=1", // serializes the form's elements.
	           success: function(data) {
                	if (data !="Success") {
                		alertifyError(data);
                	}else{
                        //Close the create user dialog
                        $("#adminDialog").dialog('close');
                        alertifySuccess('user updated');
                        //Refresh the user table
                         $("#studyTable").load('/user_table',{
                    	 study : $( "#studySelector" ).val()
						});
                	}
				}
			});
		});
	});

</script>
<div class="jumbotron">
	<form name="formulaire" id="formulaire">
		<input type="hidden" name="old_status"
			value="<?= $userObject->userStatus?>" />
		<div class="form-group row">
			<label class="col-form-label">Account status:</label>
			<div class="col-sm-10">
				<!--------------Status Selecteur ----------------------->
				<SELECT class="custom-select" name="statut">
                  <?php
				// Display current status as first one
				echo '<option value="'.$userObject->userStatus.'">'.$userObject->userStatus.'</option>';
				// Array of all possible status
				$possibleStatus;
				if ($userObject->userStatus == User::ACTIVATED || $userObject->userStatus == User::BLOCKED) {
					$possibleStatus=array(
						User::UNCONFIRMED,
						User::DEACTIVATED
					);
				}else if ($userObject->userStatus == User::UNCONFIRMED) {
					$possibleStatus=array(
						User::DEACTIVATED
					);
				}else if ($userObject->userStatus == User::DEACTIVATED) {
					$possibleStatus=array(
						User::UNCONFIRMED
					);
				}
                
				// Add in the selected possible status and change the "unconfirmed" name by "password reset" in display
				foreach ($possibleStatus as $status) {
					if ($status == "Unconfirmed") {
						echo '<option value="'.$status.'">Password Reset</option>';
					}else {
						echo '<option value="'.$status.'">'.$status.'</option>';
					}
				}
				?>
                </SELECT>
			</div>
		</div>
		<div class="form-group row">
			<label class="col-form-label">Username*:</label>
			<div class="col-sm-10">
				<input value="<?= htmlspecialchars($userObject->username) ?>" type="text"
					class="form-control" id="username" name="username" maxlength="32"
					placeholder="Username" readonly>
			</div>
		</div>

		<div class="form-group row">
			<label class="col-form-label">Last name*:</label>
			<div class="col-sm-10">
				<input value="<?= htmlspecialchars($userObject->lastName) ?>" type="text"
					class="form-control" id="last_name" name="last_name" maxlength="32"
					placeholder="Last name" required>
			</div>
		</div>

		<div class="form-group row">
			<label class="col-form-label">First name*:</label>
			<div class="col-sm-10">
				<input value="<?= htmlspecialchars($userObject->firstName) ?>" type="text"
					class="form-control" id="first_name" maxlength="32" name="first_name"
					placeholder="First name" required>
			</div>
		</div>

		<div class="form-group row">
			<label class="col-form-label">Email*:</label>
			<div class="col-sm-10">
				<input value="<?= htmlspecialchars($userObject->userEmail) ?>" type="email"
					class="form-control" id="email" maxlength="255" name="email"
					placeholder="mail@exemple.fr" required>
			</div>
		</div>

		<div class="form-group row">
			<label class="col-form-label">Phone number:</label>
			<div class="col-sm-10">
				<input value="<?= htmlspecialchars($userObject->userPhone) ?>" type="tel"
					class="form-control col-md-4" maxlength="32" name="phone">
			</div>
		</div>

		<div class="form-group row">
			<div class="form-group">
				<label class="col-form-label">Affected profiles*:</label>
			</div>
			<div class="form-group col text-center">
				<!--------------------------Display curent user's roles ----------------------->
				<SELECT id="profile" name="profile[]" class="custom-select" multiple>
                      <?php
                    
					foreach ($userRoles as $study => $existingRoles) {
						foreach ($existingRoles as $role) {
							echo '<option value="'.$role.'@'.htmlspecialchars($study).'">['.htmlspecialchars($study).'] - ['.$role.']</option>';
						}
					}
					?>
    
                    </SELECT> <input onclick="del_list('profile')"
					class="btn btn-dark" type="button" value="-">
			</div>
			<div class="form-group col text-center">
				<!--------------------------------Display all available roles ----------------------->
				<SELECT id="one" class="custom-select" name="role">
                      <?php
					$roles=array(
						User::INVESTIGATOR,
						User::MONITOR,
						User::CONTROLLER,
						User::SUPERVISOR,
						User::REVIEWER
					);
					foreach ($availableStudies as $study) {
						foreach ($roles as $role) {
							echo '<option value="'.$role.'@'.htmlspecialchars($study).'">['.htmlspecialchars($study).'] - ['.$role.']</option>';
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
				value="1" id="administrator"
				<?php if ($userObject->isAdministrator == 1) echo("checked")?>>
		</div>

		<div class="form-group row">
			<label class="col-form-label">Job:</label>
			<div class="col-sm-10">
				<!------------------------Display all jobs ----------------------->
				<SELECT class="custom-select" name="job">
                  <?php
				// Current job appear first in selector
				echo '<option value="'.$userObject->userJob.'">'.$userObject->userJob.'</option>';
				// add other possible job
				foreach ($jobs as $job) {
					if ($job != $userObject->userJob) {
						echo '<option value="'.$job.'">'.$job.'</option>';
					}
				}
				?>
                </SELECT>
			</div>
		</div>


		<div class="form-group row">
			<label class="col-form-label">Investigational center*:</label>
			<!--------------------------------- Center selector ----------------------->
			<SELECT class="custom-select" name="main_center" id="main_center"
				required>
                <?php
				// Display users's main center as first option
				echo '<option value="'.$mainCenter->code.'">['.$mainCenter->code.'] - ['.htmlspecialchars($mainCenter->name).']</option>';
                
				// Add others
				foreach ($centers as $center) {
					if ($center->code != $mainCenter->code) {
						echo '<option value="'.$center->code.'">['.$center->code.'] - ['.htmlspecialchars($center->name).']</option>';
					}
				}
				?>
              </SELECT>
		</div>

		<div class="form-group row">
			<div class="form-group">
				<label class="col-form-label">Affiliated centers:</label>
			</div>
			<div class="form-group col text-center">
				<!-- --------------------Diplay user's affiliated centers ----------------------->
				<SELECT name="afficheur_centre[]" id="afficheur_centre"
					class="custom-select" multiple>
                        <?php
						// Add option
						foreach ($usersAffiliatedCenters as $center) {
							echo '<option value="'.$center->code.'">['.$center->code.'] - ['.htmlspecialchars($center->name).']</option>';
						}
						?>
                      </SELECT> <input
					onclick="del_list('afficheur_centre')" class="btn btn-dark"
					type="button" value="-">
			</div>
			<div class="form-group col text-center">
				<!-------------------------  Display all centers ----------------------->
				<SELECT id="selecteur_centre" name="selecteur_centre"
					class="custom-select">
                    <?php
                    
					foreach ($centers as $center) {
						echo '<option value="'.$center->code.'">['.$center->code.'] - ['.htmlspecialchars($center->name).']</option>';
					}
					?>
                  </SELECT> <input
					onclick="bascule('selecteur_centre', 'afficheur_centre')"
					class="btn btn-dark" type="button" value="+">
			</div>
		</div>
		
		<div class="form-group row">
			<label class="col-form-label">Orthanc Address :</label> <input
				class="form-group col" type="text" name="orthancAddress" value="<?= htmlspecialchars($userObject->orthancAddress)?>" maxlength="255">
		</div>
		
		<div class="form-group row">
			<label class="col-form-label">Orthanc Login :</label> <input
				class="form-group col" type="text" name="orthancLogin" value="<?= htmlspecialchars($userObject->orthancLogin)?>" maxlength="255">
		</div>
		
		<div class="form-group row">
			<label class="col-form-label">Orthanc Password :</label> <input
				class="form-group col" type="password" name="orthancPassword" value="<?= htmlspecialchars($userObject->orthancPassword)?>" maxlength="255">
		</div>

		<div class="text-center">

			<br>
			<button class="btn btn-dark" type="button" name="confirmer"
				id="confirmerModify">Apply modifications</button>

		</div>
	</form>
</div>
