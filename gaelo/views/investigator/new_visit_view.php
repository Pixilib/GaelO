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
        
    	$( "#datepicker" ).datepicker({
    		yearRange: "-10:+1",
    		changeYear: true,
    		dateFormat: "yy-mm-dd",
    		onSelect: function(dateText){
    			  $('#visitDate').val(dateText);
    		}
    	});

    	$(".visitStatusSelect").on('change', function (){
        	if($("#Done").is(':checked')){
        		$("#dateDiv").show();
        		$("#reasonNotDonDiv").hide();
        	}else{
        		$("#dateDiv").hide();
        		$("#reasonNotDonDiv").show();
        	}

    	});
    
    	//Form submission if press enter
    	$("#addVisitForm").bind('keypress', function(e) {
            if(e.keyCode==13){
                 $('#send').trigger('click');
             }
        });
    	
    	$("#send").on('click', function() {
    		//Check form completion 
            var notDone=$("#Not_Done").is(':checked');
            var done=$("#Done").is(':checked');

            if(!done && !notDone) {
            	alertifyError("Set Visit done or not done");
            	return;
            }
            
            if(notDone && $("#reason").val().length==0){
            	alertifyError("For not done study a reason must be specified");
            	return;
            } else if (!notDone && $("#visitDate").val().length==0){
            	alertifyError("Choose a date");
            	return;
            } 
    		//if check OK, send form with Ajax
			$.ajax({
				type: "POST",
				dataType: 'json',
				url: '/new_visit',
				data: $("#addVisitForm").serialize()+'&validate=1', // serializes the form's elements.
				success: function(data) {
					if (data == "Success"){
						//Close dialog and update JsTree
						$("#addVisit").dialog('close');
						alertifySuccess('Visit Created');
						$('#containerTree').jstree(true).refresh();
					} else if (data == "Missing Data"){
						alertifyError('Missing Data');
					}
				}
			});
    			 
    	});
    
    	<?php
        // If patient withdrawn disable all the form
        if ($typeVisiteDispo[0] == "withdraw") {
            ?> $("#addVisitForm :input").prop("disabled", true); <?php
        }
        ?>
    		
    });
		 
</script>

<h1 class='form-control'>Create new visit</h1>

<form class="col align-self-center" id="addVisitForm">
	<label class="control-label">Please make a choice in the list:</label>
	<div class="text-center">
		<SELECT class="custom-select" name="visite" id="visite">
        	<?php     
            foreach ($typeVisiteDispo as $visitDispo) {
                echo '<option value="' . htmlspecialchars($visitDispo) . '">' . htmlspecialchars($visitDispo) . '</option>';
            }
            ?>
        </SELECT>
	</div>
	<div class="text-center" >
		<input type="radio" class="visitStatusSelect" id="Done" name="done_not_done" value="Done"
			> <label for="Done">Done</label>
		<input type="radio" class="visitStatusSelect" id="Not_Done" name="done_not_done"
			value="Not Done"> <label for="Not_Done">Not Done</label>
    	<div id="dateDiv" class="text-center" style="display:none">
    		<label class="control-label">Acquisition date:</label> <br>
    		<div id="datepicker"></div>
    		<input class="form-control" name="acquisition_date" id="visitDate"
    			type="hidden">
    	</div>
    	<div id="reasonNotDonDiv" class="text-center" style="display:none">
    		<label class="control-label">Reason:</label>
    		<input type="text" class="form-control" id="reason"
    			placeholder="Reason" maxlength="255" name="reason">
    	</div>
    	<input type="hidden" name="patient_num" id="patient_num"
    		value="<?=$patientCode?>" /> <input type="hidden" name="study"
    		id="nometude" value="<?=htmlspecialchars($study)?>" />
    	<br>
		<button class="text-center btn btn-dark" type="button" id="send">Validate</button>

	</div>

</form>
