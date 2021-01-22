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
        
		$("#sendAskUnlock").dialog({
      		autoOpen: false,
      		width : 'auto',
      		height : 'auto',
      		title: "Unlock request"
      	});
        
    	$("#ask_unlock").on('click', function(){
    		$( "#sendAskUnlock" ).load('/ask_unlock', {
    			id_visit : <?=$id_visit?>,
    	        type_visit : '<?=$type_visit?>',
    	        patient_num : <?=$patient_num?>
    		},function(){
    			$( "#sendAskUnlock" ).dialog('open');
    		});
    		
    	});
    	
		//Form Unactivation if already validated or non investigator Reviewer Role
	   <?php
		if ($validatedForm || $roleDisable) { ?>
			$('#specificForm').find('input, textarea, button, select').attr('disabled','disabled');
	   <?php
		}
    
		?>
    
		//Validation of the form, send the form with Ajax
		$( "#validate, #draft" ).on( "click", function(event) {
		  var idButton=id=event.target.id;
		  var confirmResult=false;
		  var formCheck=false;
		  if(idButton=="validate"){
			  formCheck=validateForm();
			  if (formCheck) {
				  alertify.confirm('Validate?','Are you sure you want to validate this form ? \n\nIt will no longer be possible to modify the data entered by you.', function(){ sendForm(idButton); }, function(){});
			  }else {
				  alertifyError("Fill mandatory fields")
			  }
		  }
		  else{
			  sendForm(idButton);
		  }
		});

		
	});


	function sendForm(idButton){
		$.ajax({
			type: "POST",
			//Not global to allow form send during dicom upload
			global:false,
			url: '/specific_form',
			dataType: 'json',
			data: $("#<?=$study.'_'.$type_visit?>").serialize()+"&"+idButton+"=1", // serializes the form's elements.
			success: function(answer) {

				if(!answer){
					alertifyError('Send Failed');
					return;
				}

            	<?php
				if (($_SESSION['role']) == User::INVESTIGATOR) {
				?>
					if(window.dicomUploadInUse){
						//Upload is pending, confirm sent form and unactivate form
						alertifySuccess("Form Sent, you can finish your upload");
						if(idButton=="validate"){
							$("#specificForm *").prop('disabled',true);
							$("#div_bouttons *").prop('disabled',true);
						}

						
					}else{
						// Refresh tree and content
						refreshDivContenu();
					}
            		
            	<?php
				}else if (($_SESSION['role']) == User::REVIEWER) {
				?>
					$('#contenu').empty();
					//Refresh the tree
					$('#containerTree').jstree(true).refresh();
	           <?php
				}
				?>
			}
     	});	
    };
    
</script>

<div id="specificForm">

	<h1 class='form-control bloc_bordures'>Investigation form</h1>
	<?php  
	//Add specific view in form folder
	require_once($_SERVER["DOCUMENT_ROOT"].'/data/form/'.$study.'/scripts/'.$visitGroupModality."_".$study."_".$type_visit.".php");
	?>
</div>

<div id="div_bouttons" class="col text-center">
	<div id="sendAskUnlock"></div>
    <?php
	//If not disable by role add action button
	if (!$roleDisable) {
        
		if (!$validatedForm && $visitObject->reviewAvailable) {
			// If investigator Role and form not validated, add Draft and Validate button
			?>
            	<input class="btn btn-dark" type="button" id="draft" name="draft" value="draft" /> 
        		<input class="btn btn-dark" type="button" id="validate" name="validate" value="validate" />
    		<?php 
		}else {
			//Add Ask Unlock Button
			if (!$local || ($local && $visitObject->qcStatus != Visit::QC_ACCEPTED && $visitObject->qcStatus != Visit::QC_REFUSED)) {
			?>
				<input class="btn btn-dark" id="ask_unlock" type="button" value="Ask Unlock">
    		<?php
			}
		}
	}
	?>
</div>