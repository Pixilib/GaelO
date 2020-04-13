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
	$(document).ready(function () {
		<?php
		// If not waiting corrective action or not investigator, fill the form with existing data and disable it (show only)
		if ($visitObject->stateQualityControl != Visit::QC_CORRECTIVE_ACTION_ASKED || $role != User::INVESTIGATOR) {
			?>
        		$("#form_corrective_action :input").prop("disabled", true);
        	<?php
            
			if ($visitObject->newSeriesUpload) {
			?>
        		$('input:checkbox[name="new_series"]').attr("checked", true);
        	<?php
			}
            
			if ($visitObject->investigatorFormIsCorrected) {
			?>
        		$('input:checkbox[name="information_corrected"]').attr("checked", true);
        	<?php
			}
            
			if ($visitObject->correctiveActionDecision) {
			?>
            	$('#no_corrective_action').hide();
        	<?php
			} else {
			?>
            	$('#corrective_action').hide();
        	<?php
			}
            
			if (!empty($visitObject->otherCorrectiveAction)) {
				?>
                $('#other_comment').val("<?=htmlspecialchars($visitObject->otherCorrectiveAction)?>");
                <?php 
			}
		}
        
		// If Form not needed deactive form checkbox
		if (!$visitType->localFormNeeded) {
			?> $('input:checkbox[name="information_corrected"]').prop("disabled", true); <?php
		}
        
		// If Image QC not needed deactive form checkbox
		if (!$visitType->qcNeeded) {
			?> $('input:checkbox[name="new_series"]').prop("disabled", true); <?php
		}
		?>
    
    	 $('#no_corrective_action, #corrective_action').click(function (event) {
             //Get clicked button for final decision
             var id=event.target.id;
             var check;
             //If refuse or ask corrective action check that at least one item is refused
             if (id=="corrective_action"){
                 //Check at least one item is selected before sending corrective action applied
            	 check=$('#information_corrected, #new_series').is(':checked');
            	 if(!check) alertifyError("Please select your corrective action");
             }else{
            	 check=$('#other_comment').val().length>0;
            	 if(!check) alertifyError("Please enter comment to refuse corrective action");
             }
             
             //If checks OK send the form and refresh the parent div containing this form
             if( check ){
    				$.ajax({
    		           type: "POST",
    		           dataType: 'json',
    		           url: '/corrective_action',
    		           data: $("#form_corrective_action").serialize()+"&"+id+"=1", // serializes the form's elements.
    		           success: function(data) {
    		        	   if (data == "Form Missing"){
    		        		   alertifyError("Form must be validated to send corrective action");   
    		        	   } else if (data == "Success"){
    		        		   refreshDivContenu();
    		        	   } 					
    
    				}
    			});		
             }
         });
         
	});
</script>

<form class="align-self-center" method="POST"
	id="form_corrective_action">

	<label class="control-label">A corrective action has been requested</label>
	<div id="container" class="bloc_bordures_orange">

		<div class="controlerForm" id="container_quality_control"></div>

		<label class="control-label">Please describe the corrective action you
			applied</label> <br>

		<div class="col">
			<input type="checkbox" id="new_series" name="new_series" value="1"> <label
				for="new_series">New series uploaded</label>
		</div>

		<div class="col">
			<input type="checkbox" id="information_corrected"
				name="information_corrected" value="1"> <label
				for="information_corrected">Information corrected in the
				investigation form</label>
		</div>

		<div class="col">
			<label for="other">Other</label> <input type="text"
				class="form-control" id="other_comment" maxlength="255" name="other_comment">
		</div>
		<br>

		<div class="col text-center">
			<!-- Saving current variables -->
			<input type="hidden" value="<?=$patient_num?>" name="patient_num"
				id="patient_num"> <input type="hidden" value="<?=$type_visit?>"
				name="type_visit" id="type_visit"> <input type="hidden"
				value="<?=$id_visit?>" name="id_visit" id="id_visit">

			<button name="no_corrective_action" id="no_corrective_action"
				type="button" class="btn btn-warning">No corrective action possible</button>
			<button name="corrective_action" id="corrective_action" type="button"
				class="btn btn-warning">Corrective action applied</button>
		</div>
		<br>
	</div>

</form>
