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
        //Listener for change study status in selects
        $('#removeActivateVisit').click(function(e) {
        	$("#activatedStudies option:selected").appendTo( "#unactivatedStudies" );
        	$("#activatedStudies option:selected").remove();
        });

        $('#addActivateVisit').click(function(e) {
        	$("#unactivatedStudies option:selected").appendTo( "#activatedStudies" );
        	$("#unactivatedStudies option:selected").remove();
        });

      //Select all the visit select and send the form
        $("#studyStatusSubmit").on("click",function(eve){
            $("#activatedStudies,#unactivatedStudies").find("option").prop("selected", true);
            $.ajax({
 	           type: "POST",
 	           dataType: 'json',
 	           url: '/study_activation',
 	           data: $("#studyActivationForm").serialize(), // serializes the form's elements.
 	           success: function(data) {
                 	if (data !="Success") {
                 		alertifyError(data);
                 	}else{
                         //Close the create user dialog
                         $("#adminDialog").dialog('close');
                         alertifySuccess('Study Activations Modified');
                         //Refresh the admin div
                         $("#mainDiv").load('/administrator');
                 	}
 				}
 			});
        })
    	
    });

</script>
<div class="jumbotron">
	<form method="post" id="studyActivationForm">
		<div class="form-group row">
			<div class="col">
				<label class="col-form-label">Activated Studies </label> 
				<SELECT id="activatedStudies" class="form-group col text-center"
					name="activatedStudies[]" multiple>
					<?php
					foreach ($activatedStudiesDb as $study) {
						echo '<option value="'.htmlspecialchars($study).'">'.htmlspecialchars($study).'</option>';
					}
					?>
					</SELECT>
			</div>

			<div class="col text-center jumbotron ">
				<input id="addActivateVisit" class="btn btn-dark" type="button"
					value=" <-- "> <input id="removeActivateVisit" class="btn btn-dark"
					type="button" value=" --> ">
			</div>

			<div class="col">
				<label class="col-form-label">Unactivated Studies</label>
				<!------------------------- Visit List ----------------------->
				<SELECT id="unactivatedStudies" class="form-group col text-center"
					name="unactivatedStudies[]" multiple>
                <?php                   
				foreach ($unactivatedStudiesDb as $study) {
					echo '<option value="'.htmlspecialchars($study).'">'.htmlspecialchars($study).'</option>';
				}
				?>
                </SELECT>
			</div>
		</div>

		<div class="text-center">
			<button type="button" id="studyStatusSubmit" class="btn btn-primary">Apply</button>
		</div>
	</form>
</div>