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

		const visitsToCreate= <?=json_encode($typeVisiteDispo)?>

		function isPatientWithdrawn(visitNames){
			if(visitNames[0]=="<?=Patient::PATIENT_WITHDRAW ?>"){
				$("#addVisitForm :input").prop("disabled", true);
			}

		}
		
		if(Object.keys(visitsToCreate).length ==1 ){
			let firstKey=Object.keys(visitsToCreate)[0];
			isPatientWithdrawn(visitsToCreate[firstKey]['visitsName'])
			visitsToCreate[firstKey]['visitsName'].forEach(visit=>{
				$("#visite").append(new Option(visit, visit))
			})
			$("#modalityDiv").hide()

		}else{
			$("#modalityGroup").prepend(new Option("Choose", "Choose"));
			$("#modalityGroup option").eq(0).prop('selected', true);
		}

		$('#datePicker').datepicker({
			toggleActive: true,
			format: "yyyy-mm-dd",
			endDate: '0d'
		});
		
		$('#datePicker').datepicker().on('changeDate', function(e) {
			$('#visitDate').val($('#datePicker').datepicker("getFormattedDate"));
		});

		$("#modalityGroup").on('change', function() {
			$('#visite').empty()
			
			let selectedModality= $( "#modalityGroup option:selected" ).text().trim();
			let possibleVisits=visitsToCreate[selectedModality]['visitsName']
			possibleVisits.forEach(visit=>{
				$("#visite").append(new Option(visit, visit))
			})
			

		});

		$("#reasonNotDoneSelect").on('change', function(){

			let selectedValue = $("#reasonNotDoneSelect").val()
			if (selectedValue === 'Other'){
				$("#reason").val('')
				$("#reason").show()
			}else if(selectedValue !== ''){
				$("#reason").hide()
				$("#reason").val(selectedValue)
			}else if (selectedValue ===''){
				$("#reason").val('')
				$("#reason").hide()
			}

		})

    	$(".visitStatusSelect").on('change', function (){
        	if($("#Done").is(':checked')){
        		$("#dateDiv").show();
				$("#reasonNotDoneSelect").val('')
				$("#reason").val('')
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
				url: 'scripts/visit.php',
				data: $("#addVisitForm").serialize(), // serializes the form's elements.
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
    		
    });
		 
</script>

<h1 class='form-control'>Create new visit</h1>

<form class="col align-self-center" id="addVisitForm">
	<div id="modalityDiv">
		<label class="control-label">Modality:</label>
		<SELECT class="custom-select" id="modalityGroup" name="groupId" >
			<?php
			foreach ($typeVisiteDispo as $modality=>$details) {
				?>
				<option value="<?=$details['groupId']?>"> <?=$modality?> </option>
				<?php
			}
			?>
		</SELECT>
	</div>
	<label class="control-label">Visit :</label>
	<div class="text-center">
		<SELECT class="custom-select" name="visite" id="visite">
        </SELECT>
	</div>
	<div class="text-center" >
		<input type="radio" class="visitStatusSelect" id="Done" name="done_not_done" value="Done"
			> <label for="Done">Done</label>
		<input type="radio" class="visitStatusSelect" id="Not_Done" name="done_not_done"
			value="Not Done"> <label for="Not_Done">Not Done</label>
    	<div id="dateDiv" class="text-center" style="display:none">
			<label class="control-label">Acquisition date:</label> <br>
			<div id="datePicker"></div>
    		<input class="form-control" name="acquisition_date" id="visitDate"
    			type="hidden">
    	</div>
    	<div id="reasonNotDonDiv" class="text-center" style="display:none">
			<label class="control-label">Reason:</label>
			<SELECT class="form-control" id="reasonNotDoneSelect">
				<option value="" > Choose </option>
				<option value="Not Performed" >Not Performed</option>
				<option value="Image Lost" >Image Lost</option>
				<option value="Patient Withdrawn">Patient Withdrawn</option>
				<option value="Other">Other</option>
			</SELECT>
    		<input type="text" class="form-control" id="reason"
    			placeholder="Reason" maxlength="255" name="reason" style="display:none">
    	</div>
    	<input type="hidden" name="patient_num" id="patient_num"
    		value="<?=$patientCode?>" /> <input type="hidden" name="study"
    		id="nometude" value="<?=htmlspecialchars($study)?>" />
    	<br>
		<button class="text-center btn btn-dark" type="button" id="send">Validate</button>

	</div>

</form>
