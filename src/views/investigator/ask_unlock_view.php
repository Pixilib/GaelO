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
		$('#ask_unlock_send').click(function () {
			//SK VOIR PQ CE TABLEAU ZERO
			var check=checkForm( $("#ask_unlock_form")[0]);
			if(check){
				$.ajax({
					type: "POST",
					dataType: 'json',
					url: '/ask_unlock',
					data: $("#ask_unlock_form").serialize()+"&validate=1", // serializes the form's elements.
					success: function(data) {
						if (data == "Success"){
							alertifySuccess('Unlock Request Sent');  
							$( "#sendAskUnlock" ).dialog('close');
						} else if (data == "Missing Reason"){
							alertifyError('Missing Reason');
						} 						
					}
				});	
			}	
         });
    });  
</script>


<form class="col align-self-center" id="ask_unlock_form">

	<label class="control-label">In order to help getting our service
		better, please specify the reason why you request the unlock of the
		form</label>
	<div class="text-center">
		<input type="text" class="form-control" id="request"
			placeholder="request" name="request" maxlength="255" required>
	</div>

	<input type="hidden" name="patient_num" id="patient_num"
		value="<?=$patient_num ?>" /> 
	<input type="hidden" name="id_visit"
		id="id_visit" value="<?=$id_visit?>" /> 
	<input type="hidden"
		name="type_visit" id="type_visit" value="<?=htmlentities($type_visit)?>" />

	<div class="text-center">
		<button class="text-center btn btn-dark" type="button"
			id="ask_unlock_send" name="validate">Submit request</button>
	</div>

</form>