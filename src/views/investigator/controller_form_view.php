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
	$(document).ready(function () {
        <?php
		if ($visitObject->formQualityControl == true) {
			?> $('input:radio[name="formDecision"]').filter('[value="accepted"]').attr("checked", true); <?php
		}else {
			?> $('input:radio[name="formDecision"]').filter('[value="refused"]').attr("checked", true); <?php
		}
        
		if ($visitObject->imageQualityControl == true) {
			?> $('input:radio[name="imageDecision"]').filter('[value="accepted"]').attr("checked", true); <?php
		}else {
			?> $('input:radio[name="imageDecision"]').filter('[value="refused"]').attr("checked", true); <?php
		}
		?>
          //Add comment data
          $("#formComment").val("<?=htmlentities($visitObject->formQualityComment)?>");
          $("#imageComment").val("<?=htmlentities($visitObject->imageQualityComment)?>");
          <?php
			// Form deactivation if not controller or controller but not in not done or wait definitive conclusion status
			if ($_SESSION['role'] != User::CONTROLLER 
				|| ($_SESSION['role'] == User::CONTROLLER 
						&& !in_array($visitObject->stateQualityControl, array(Visit::QC_NOT_DONE, Visit::QC_WAIT_DEFINITVE_CONCLUSION)))) {
			?>
				$("#controler_form :input").prop("disabled", true); 
            <?php
			}
        
		// If form Not needed unactivate the form option
		if (!$visitType->localFormNeeded) {
			?>$("#accepted1").attr( 'checked', true )
			//Disable other radiobutton to force send the accepted value (disable make the choice unsent)
			$("#refused1").prop("disabled", true);
			$("#formComment").prop("readonly", true);
			<?php
		}
		// if image Qc not Need unactivate the form option
		if (!$visitType->qcNeeded) {
			?>$("#accepted2").attr( 'checked', true )
			$('#refused2').prop("disabled", true); <?php
		}
		?>
	});

    $('#refuse, #ask_corrective_action, #accept').click(function (event) {
        //Get clicked button for final decision
        var id=event.target.id;
        var check=false;
        
        //If refuse check that both items are refused
        if (id=="refuse"){
			if( ! $("#refused2").is( ":checked" ) && !$("#refused1").is( ":checked" )) {
				alertifyError('To Refuse quality control, at least one item should be refused');
			} else if (  ($("#refused2").is( ":checked" ) && $('#imageComment').val().length===0 ) 
					|| ( $("#refused1").is( ":checked" ) && $('#formComment').val().length===0) ) {

				alertifyError('To Refuse quality control, refused item should have associated comment');
        	} else {
				
				if(confirm( "Refuse decision, visit will not be send to reviewers, are you sure?" )){
                	check=true
            	}
        	}
    	//If corrective action check that at least one item is refused
        }else if(id=="ask_corrective_action"){
        	if ( ($("#refused2").is( ":checked" ) && !($('#imageComment').val().length===0) ) || ( $("#refused1").is( ":checked" ) && !($('#formComment').val().length===0) ) ){
            	check=true;
        	} 
        	else{
        		alertifyError('For Corrective Action Request, at least one of the item should be refused with an associated comment');
        	}
            
        }
        //If accept check that all item are validated
        else if (id="accept"){
        	if ( $("#accepted1").is( ":checked" ) && $("#accepted2").is( ":checked" )){
        		check=true
        	}
        	else{
        		alertifyError('all Items must be accepted for final acceptance');
        	}
    	}
        //If checks OK send the form and refresh the parent div containing this form
        if( check ){
			$.ajax({
	           type: "POST",
	           url: '/controller_form',
	           data: $("#controler_form").serialize()+"&"+id+"=1", // serializes the form's elements.
	           success: function(data) {
  		           //Get the div containing the visit interface
  		        	var parent_id = $('#controler_form').parent().parent().attr('id');
  		        	//empty it
  		        	$('#'+parent_id).empty();
  		        	//Refresh Tree
  		        	$('#containerTree').jstree(true).refresh();
				}
         	});		
        }
    });
            
</script>

<label class="control-label">Quality control form</label>
<form method="post" id="controler_form" class="form-horizontal">
	<div id="container_quality_control" class="bloc_bordures">
		<div class="row">
			<div class="col"></div>
			<div class="col text-center">
				<label class="control-label">Accepted</label>
			</div>
			<div class="col text-center">
				<label class="control-label">Refused</label>
			</div>
			<div class="col">
				<label class="control-label">Comment</label>
			</div>
		</div>
		<div class="row">
			<div class="col">
				<label class="control-label">Investigator form</label>
			</div>
			<div class="col text-center">
				<input type="radio" id="accepted1" name="formDecision"
					value="accepted">
			</div>
			<div class="col text-center">
				<input type="radio" id="refused1" name="formDecision"
					value="refused" checked>
			</div>
			<div class="col">
				<input type="text" class="form-control" maxlength="255" id="formComment"
					name="formComment">
			</div>
		</div>
		<div class="row">
			<div class="col">
				<label class="control-label">Image series</label>
			</div>
			<div class="col text-center">
				<input type="radio" id="accepted2" name="imageDecision"
					value="accepted">
			</div>
			<div class="col text-center">
				<input type="radio" id="refused2" name="imageDecision"
					value="refused" checked>
			</div>
			<div class="col">
				<input type="text" class="form-control" maxlength="255" id="imageComment"
					name="imageComment">
			</div>
		</div>
		<div class="text-center">
			<input type="hidden" value="<?=$patient_num?>" name="patient_num"
				id="patient_num"> <input type="hidden" value="<?=$type_visit?>"
				name="type_visit" id="type_visit"> <input type="hidden"
				value="<?=$id_visit?>" name="id_visit" id="id_visit">

              <?php
			// If controller role add button to validate form submission
			if ($role == User::CONTROLLER 
					&& in_array($visitObject->stateQualityControl, array(Visit::QC_NOT_DONE, Visit::QC_WAIT_DEFINITVE_CONCLUSION))) {
				?>
                <button name="refuse" id="refuse" type="button"
				class="btn btn-danger">Refuse</button>
			<button name="ask_corrective_action" id="ask_corrective_action"
				type="button" class="btn btn-secondary">Ask corrective action</button>
			<button name="accept" id="accept" type="button"
				class="btn btn-success">Accept</button>
                <?php
			}
			?>

        </div>
	</div>
</form>