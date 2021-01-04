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

		$("#reasonWithdrawSelect").on('change', function(){

			let selectedValue = $("#reasonWithdrawSelect").val()
			if (selectedValue === 'Other'){
				$("#reason").val('')
				$("#reason").show()
			}else if(selectedValue !== 'N/A'){
				$("#reason").hide()
				$("#reason").val(selectedValue)
			}else if (selectedValue ==='N/A'){
				$("#reason").val('')
				$("#reason").hide()
			}

		})

		$('#datePickerPatient').datepicker({
			toggleActive: true,
			format: "yyyy-mm-dd"
		});

				
		$('#datePickerPatient').datepicker().on('changeDate', function(e) {
			let formattedDate=$('#datePickerPatient').datepicker("getFormattedDate")
			$('#withdraw_date').val(formattedDate);
		});
    
        $( "#validateChangeStatus" ).on('click', function() {
        	if( $('#withdraw').val()==1 && ( !$('#withdraw_date').val().length>0 || !$('#reason').val().length>0) ){
        		alertifyError('Choose Withdraw date and enter a reason');
            	return;
        	};
        	if( $('#withdraw').val()==0 && !$('#reason').val().length>0){
        		alertifyError('Enter a reason for withdraw canceling');
            	return;
        	};
            $.ajax({
    	           type: "POST",
    	           dataType: 'json',
    	           url: '/change_patient_status',
    	           data: $("#changePatientStatusForm").serialize()+"&validate=1", // serializes the form's elements.
    	           success: function(data)
    	           {
    		           if(data=="Success"){
    		        	   //Refresh the patient supervisor Div
       	   		        	$('#patientInfoSupervisor').load('/patient_infos', {
       	   						patient_num : <?=$patientObject->patientCode?>
       	   					});
       	   		        	$("#change_patient_status").dialog('close');
       	   		        	alertifySuccess('Modified');
    		           }else{
    		        	   alertifyError('Error');
    		           }
       		          
    
    	           }
             	});	
        });	
    
        <?php 
		//If patient withdraw set the hidden withdraw value to 0 and hide the date selector
		if ($patientObject->patientWithdraw) {
		?>
            $("#dateWithdraw").hide();
            $("#withdraw").val('0');
       <?php 
		}
		?>
    	
    });

</script>
        	
<form id="changePatientStatusForm">
    	<?php 
		if (!$patientObject->patientWithdraw) {
		?>
            <label class="control-label">Declare patient withdrawal from the study</label>
		<?php 
		}else {
		?>
            <label class="control-label">Cancel Withdrawal (re-inclusion)</label>
		<?php 
		}
		?>  
        <div id="dateWithdraw">
        	<div id="datePickerPatient"></div>
            <input class="form-control" name="withdraw_date" id="withdraw_date" type="hidden" >
        </div>
        <label class="control-label">Reason:</label>

        <div class="text-center">
			<?php 
			if (!$patientObject->patientWithdraw) {
				?>
				<SELECT class="form-control" id="reasonWithdrawSelect">
					<option value="N/A"> Choose </option>
					<option value="Withdraw Consent" >Withdraw Consent</option>
					<option value="lost to follow up" >lost to follow up</option>
					<option value="Not Included" >Not Included</option>
					<option value="Excluded"> Excluded </option>
					<option value="Progression" >Progression</option>
					<option value="Death" >Death</option>
					<option value="Toxicity" >Toxicity</option>
					<option value="Investigator Decision" >Investigator Decision</option>
					<option value="Other"> Other </option>
				</SELECT>
				<input type="text" class="form-control" id="reason" placeholder="Reason" name="reason" maxlength="255" style="display:none">
				<?php
			}else{
				?>
					<input type="text" class="form-control" id="reason" placeholder="Reason" name="reason" maxlength="255">
				<?php
			}
			?>

        	
        </div>
        <br>
        <input type="hidden" name="patient_num" id="idpatient" value="<?=$patientObject->patientCode?>">
        <input type="hidden" name="withdraw" id="withdraw" value=1>
        <div class="text-center">
        	<button class="text-center btn btn-dark" type="button" name="validate" id="validateChangeStatus">Validate</button>
    	</div>    
</form>