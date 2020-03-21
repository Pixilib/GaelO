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

	$(document).ready( function () {

		$("#countrySelect").chosen();

		$('#parseJson').on('click', function() {
			let fr = new FileReader();
			fr.readAsText($("#fileImportCenter")[0].files[0]);
			fr.onload = () => {
				fillTable(JSON.parse(fr.result));
			}	
		});

		function fillTable( jsonData ){
			for (var centercode of Object.keys(jsonData)) {
				if( $('#centersTable td:contains('+centercode+')').length>0){
					console.log(centercode + 'already in list, not modified')
				}else{
					$('#centersTable').append('<tr class="bg-warning"><td contenteditable="true">'+ centercode +'</td><td contenteditable="true">'+jsonData[centercode]['centerName']+'</td><td contenteditable="true">'+jsonData[centercode]['countryCode']+'</td></tr>');
				}
			}
		}

		$('#centersTable').DataTable( {
	        "scrollY":        "200px",
	        "scrollCollapse": true,
	        "paging":         false
	    } );

		$('#addCenter').on('click', function(){
			if( $('#centerNumber').val().length>0 && $('#centerDescription').val().length>0 && $('#countrySelect').val().length>0){
				var centerNumber=$('#centerNumber').val();
				console.log($('#centersTable td:contains( "'+centerNumber+'" )').text());
				if( $('#centersTable td:contains('+centerNumber+')').length>0){
					alertifyError("Center Code already in list");
				}else{
					$('#centersTable'). append('<tr class="bg-warning"><td contenteditable="true">'+ $('#centerNumber').val()+'</td><td contenteditable="true">'+$('#centerDescription').val()+'</td><td contenteditable="true">'+$('#countrySelect').val()+'</td></tr>');
					$('#centerNumber').val()='';
					$('#centerDescription').val()='';
				}
			}else{
          		alertifyError("Filling in Number, name and country is mandatory");
            };
		});

        $('#sendModifyCenter').on('click', function(){
        	var tbl = $('#centersTable tbody tr').get().map(function(row) {
        		  return $(row).find('td').get().map(function(cell) {
        		    return $(cell).html();
        		  });
        		});
        	var results = JSON.stringify(tbl);
			$.ajax({
	           type: "POST",
	           dataType: 'json',
	           url: '/modify_centers',
	           data: { centersData : results}, // serializes the form's elements.
	           success: function(data) {
		           console.log(data);
                	if (data !="Success") {
                		alertifyError(data);
                	}else{
                        //Close the create user dialog
                        $("#adminDialog").dialog('close');
                        alertifySuccess('Centers updated');
                	}
				},
				error: function( jqXHR, textStatus, errorThrown ){
					console.log("Error:");
					console.log(textStatus);
					console.log(errorThrown);
				}	
			});
        });
        
	});
	
</script>

<div class="jumbotron">

	<div class="text-center">
    		<table class="table table-striped" id="centersTable">
    			<thead>
        			<tr>
            			<td>Code</td>
            			<td>Name</td>
            			<td>Country</td>
        			</tr>
    			</thead>
    			<tbody>
					<?php
                    // Add all existing centers in the selector
                    foreach ($centers as $center) {
                        ?>
                        <tr>
                        <td><?=$center->code ?></td>
                        <td contenteditable="true"><?=htmlspecialchars($center->name)?></td>
                        <td contenteditable="true"><?=$center->countryCode ?></td>
                        </tr>
                        <?php
                    }
                    ?>
    			</tbody>
    		</table>
		</div>
	<div>
		<input type="file" id="fileImportCenter">
		<button id="parseJson" type="button" class="btn btn-dark">Import Json</button>
	</div>
	<div class="container mt-5">
		<form name="formulaire" id="changeCenterForm" class="text-center">
			<div class="row">
				<label class="col col-form-label">Number:</label> 
				<input type="number"
					class="col form-control" id="centerNumber" maxlength="11"> 
			</div>
			<div class="row">
				<label class="col col-form-label">Description:</label> 
				<input type="text"
					class="col form-control" id="centerDescription" maxlength="32">
			</div>
			<div class="row">
				<label class="col col-form-label">Country:</label> 
				<select
					class="col form-control" id="countrySelect">
					<?php foreach ($countries as $country){
					?>
						<option value="<?= $country['country_code']?>"> <?=$country['country_us']?></option>
					<?php 
					}?>
				</select> 
			</div>
			<div class="text-center">
				<input class="btn btn-dark" type="button" id="addCenter" value="+">
			</div>
		</form>
	</div>
	<div class="mt-5 text-center">
		<button name="confirmer" type="button" class="btn btn-dark text-center" id="sendModifyCenter">Apply
			modifications</button>
	</div>


</div>
