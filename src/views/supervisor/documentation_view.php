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
        $( "#saveButton" ).on('click', function() {
    		$.ajax({
    			type: "POST",    		           
    			dataType: 'json',
    			url: '/documentation_supervisor',
    			data: $("#documentationForm").serialize()+"&validate=1", // serializes the form's elements.
    			success: function(data) {
    				if(data=="Success"){
    					$("#documentation").dialog('close');
                    	alertifySuccess('Documentation Update Success');
                    }else{
                    	alertifyError('Error');
                    }
    				
    			}
    		});	
        });	
    });

</script>
<div>
	<form id="documentationForm">
		<table class="table table-striped text-center">
			<thead class="thead-light">
				<tr>
					<th scope="col"></th>
					<th scope="col">Name of the document</th>
					<th scope="col">Version</th>
					<th scope="col">Application date</th>
					<th scope="col">Inv.</th>
					<th scope="col">Cont.</th>
					<th scope="col">Mon.</th>
					<th scope="col">Rev.</th>
					<th scope="col">Deleted</th>
				</tr>
			</thead>
			<tbody>
				<?php
				//For each documentation database entry make checkbox to select visibility (and check them depending on database value)
				foreach ($documentationObjects as $documentation) {
				    ?>
				    <tr><td><img class="icon" src="assets/images/download.png" alt="Download" onclick="downloadDocumentation(<?=$documentation->documentId ?>)"></td>
				    <td><?= htmlspecialchars($documentation->documentName)?></td>
				    <td><?= htmlspecialchars($documentation->documentVersion)?></td>
				    <td><?= $documentation->documentDate?></td>
				    <td><input type="checkbox" name="inv<?= $documentation->documentId?>" value="1" class="form-check-input" <?php if($documentation->accessInvestigator) echo("checked")?>></td>
				    <td><input type="checkbox" name="cont<?= $documentation->documentId?>" value="1" class="form-check-input" <?php if($documentation->accessController) echo("checked")?>></td>
				    <td><input type="checkbox" name="mon<?= $documentation->documentId?>" value="1" class="form-check-input" <?php if($documentation->accessMonitor) echo("checked")?>></td>
				    <td><input type="checkbox" name="rev<?= $documentation->documentId?>" value="1" class="form-check-input" <?php if($documentation->accessReviewer) echo("checked")?>></td>
				    <td><input type="checkbox" name="deleted<?= $documentation->documentId?>" value="1" class="form-check-input" <?php if($documentation->deleted) echo("checked")?>></td>
					<?php
				} ?>

			</tbody>
		</table>
		<input id="saveButton" class="btn btn-success" type="button" name="Save" value="Save" class="btn btn-dark">
	</form>
	<!-- Form for file documentation upload -->
	<form  method="POST"  class="mt-3" action="/documentation_upload" enctype="multipart/form-data">
		<input type="file" class="btn" name="documentationfile" value="Set documentation">
		Version Name:<input type="text" name="version" maxlength="10" value="1.0">
		<input type="submit" class="btn btn-info" name="envoyer" id="submitDocumentation" value="Send File">
	</form>
</div>