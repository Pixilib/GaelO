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

<script>
    $(document).ready(function () {
    	//Load the withdraw form in the dialog
    	$( "#change_patient" ).on('click', function () {
    		$( "#change_patient_status" ).load('/change_patient_status', {
    					patient_num : <?=$_POST['patient_num'] ?> } , function() {
    						$( "#change_patient_status" ).dialog('open');
        	});
			
    		
    	});

    	$("#editPatient").on('click', function () {
        	//Make allowed cells editable
    		$( ".patientDataEditable" ).attr('contenteditable','true');
    		$( ".patientDataEditable" ).css('background-color', 'orange');
			//Replace the edit button by the apply button
    		var button = document.createElement("INPUT"); 
            button.className = "btn btn-danger";    
            button.setAttribute("id", "applyEditPatient");
            button.setAttribute("type", "button");
            button.setAttribute("value", "Apply Changes");
            $( "#columnEdit" ).append(button);
            $("#editPatient").hide();
    	});

    	$('#patientInfoSupervisor').on('click', '#applyEditPatient', function () {
    		var newInitials=$("#initals").text();
    		var newGender=$("#gender").text();
    		var newBirthDate=$("#birthdate").text();
    		var newRegistrationDate=$("#registrationDate").text();
    		var newCenter=$("#center").text();
    		var newInvestigator=$("#investigator").text();

            $.ajax({
        		type: "POST",
        		url: '/patient_infos',
        		dataType: 'json',
        		data: {patient_num : <?=$_POST['patient_num'] ?>, initials : newInitials, gender : newGender, birthdate : newBirthDate, 
            		registrationDate : newRegistrationDate, center : newCenter, investigator : newInvestigator },
        		success: function(data) {
        			if(data==false){
        				alertifyError('Failure, check data : Code center, date format... ');
        			}else{
        				//Refresh the patient supervisor Div
        				alertifySuccess("Updated");
        				$('#patientInfoSupervisor').off('click');
        	        	$('#patientInfoSupervisor').load('/patient_infos', {
        					patient_num : <?=$_POST['patient_num'] ?>
        				});
        	        	
        			}
        			
        		},
        		error: function( jqXHR, textStatus, errorThrown ){
        			console.log("Error:"+jqXHR);
        			console.log(textStatus);
        			console.log(errorThrown);
        			
        		}	
        	});
        	
    		
    	     console.log("yeahhhh!!! but this doesn't work for me :(");
    	});
    
    	//Dialog to set patient withdrawn
    	$("#change_patient_status").dialog({
    		autoOpen: false,
    		width : 'auto',
    		height : 'auto',
    		title: "Change Patient Status"
    	});
    	
    });
    
    //Reactivate visit defined by visitID (set on Onclick of reactivate button)
    function reactivateVisit(idVisit) {
        //Get the Get parameters
        $.ajax({
    		type: "POST",
    		url: 'scripts/reactivate_visit.php',
    		dataType: 'json',
    		data: {visit_id : idVisit},
    		success: function(data) {
    			if(data==false){
    				alertifyError('Activation impossible, all visits for this visit type should be deleted before reactivation');
    			}else{
    				//Refresh the patient supervisor Div
    				alertifySuccess("Reactivated");
    	        	$('#patientInfoSupervisor').load('/patient_infos', {
    					patient_num : <?=$_POST['patient_num'] ?>
    				});
    	        	
    			}
    			
    		},
    		error: function( jqXHR, textStatus, errorThrown ){
    			console.log("Error:");
    			console.log(textStatus);
    			console.log(errorThrown);
    			
    		}	
    	});
    	
    };
</script>

<div id="change_patient_status"> </div>
<div id="patientInfoSupervisor">

	<br><table id='tab_patient_super' class='table table-striped table-sm'>
		<tr>
			<td colspan=2><b>Patient information</b></td>
		</tr>
		<tr>
			<td >Patient number</td>
			<td><?=$patientObject->patientCode?></td>
		</tr>
		 <tr>
			<td>Initials</td>
			<td  class="patientDataEditable" id="initals" ><?=$patientObject->patientLastName, $patientObject->patientFirstName?></td>
		</tr>
		<tr>
			<td>Gender</td>
			<td class="patientDataEditable" id="gender"><?=$patientObject->patientGender?></td>
		</tr>
		<tr>
			<td>Birthday</td>
			<td class="patientDataEditable" id="birthdate"><?=$patientObject->patientBirthDate?></td>
		</tr>
		<tr>
			<td>Registration date</td>
			<td class="patientDataEditable" id="registrationDate"><?=$patientObject->patientRegistrationDate?></td>
		</tr>
		<tr>
			<td>Number investigator center</td>
			<td class="patientDataEditable" id="center"><?=$patientObject->patientCenter?></td>
		</tr>
		<tr>
			<td>Principal investigator</td>
			<td class="patientDataEditable" id="investigator"><?=htmlspecialchars($patientObject->patientInvestigatorName)?></td>
		</tr>
		<tr>
			<td>Patient status</td>
			<td><?php if ($patientObject->patientWithdraw) echo("Withdrawn"); else echo("Included")?></td>
			<td>
			<button class='btn btn-danger' id='change_patient'>Modify</button>
			</td>
		</tr>
		<tr>
			<td>Withdrawal date</td>
			<td><?=$patientObject->patientWithdrawDateString?></td>
		</tr>
		<tr>
			<td>Withdrawal reason</td>
			<td><?=htmlspecialchars($patientObject->patientWithdrawReason)?></td>
		</tr>
		<tr>
			<td colspan=2 id="columnEdit" class="text-right"><input type="button" class="btn btn-danger" value="Edit Patient" id="editPatient"></td>
		</tr>
	</table>


	 <div style="overflow-x:auto;">
	 	<table id='tab_visit_super' class='table table-striped'>
			<tr>
			<th colspan=7>Visit information</th>
			</tr>
			<tr>
			<td rowspan=2>Visit</td>
			<td rowspan=2>Modality</td>
			<td colspan=4>Visit status</td>
			<td rowspan=2>Review Status</td>
			<td rowspan=2>Visit Deleted</td>
			</tr>
			<tr>
			<td>Visit</td>
			<td>Series upload</td>
			<td>Investigator form</td>
			<td>Quality control</td>
			</tr>
		<?php foreach ($visitsObjects as $visit) { ?>
			<tr>
				<td><?=$visit->visitType?></td>
				<td><?=$visit->visitGroupObject->groupModality?></td>
				<td class="visitDetails" title="visit" ><?=$visit->statusDone?></td>
				<td class="visitDetails" title="serie" ><?=$visit->uploadStatus?></td>
				<td class="visitDetails" title="form" ><?=$visit->stateInvestigatorForm?></td>
				<td class="visitDetails" title="quality" ><?=$visit->stateQualityControl?></td>
				<td class="visitDetails" title="review_div" ><?=$visit->reviewStatus?></td>
			<?php 
			if ($visit->deleted) {
			?>
				<td class="visitDetails">
					<input class="btn btn-danger" type="button" value="Reactivate Visit" onclick="reactivateVisit(<?=$visit->id_visit?>)">
				</td>
			<?php 
			}else { ?>
				<td class="visitDetails" title="deleted" >Active</td>
			<?php 
			} ?>
			</tr>
		<?php 
		}
		?>
		</table>
	</div>

</div>