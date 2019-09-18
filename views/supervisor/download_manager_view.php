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
    
    	//Dialog to send to peers (to reviewers)
    	$("#peers").dialog({
    		autoOpen: false,
    		width : 'auto',
    		height : 'auto',
    		title: "Select Peers",
    		modal : true,
    		closeOnEscape: false
    
    	});
    
    	//Listeners for activation / deactivation of sections of the global form
        $('#allcenters').click(function(e) {
            if (document.getElementById('allcenters').checked){
    			$("[name='centers[]']").prop("disabled", true);
            }else{
            	$("[name='centers[]']").prop("disabled", false);
            }
        });
    
        $('#allpatients').click(function(e) {
            if (document.getElementById('allpatients').checked){
    			$("[name='patients']").prop("disabled", true);
            }else{
            	$("[name='patients']").prop("disabled", false);
            }
        });
    
        $('#allvisits').click(function(e) {
            if (document.getElementById('allvisits').checked){
    			$("[name='visits[]']").prop("disabled", true);
            }else{
            	$("[name='visits[]']").prop("disabled", false);
            }
        });
    
        //Listners to display the general or the manual download section
        $('#btn_general').click(function(e) {
        	$( '#manual' ).hide();
        	$( '#general' ).show();	
        });
    
        $('#btn_manual_download').click(function(e) {
        	$( '#manual' ).show();
        	$( '#general' ).hide();
        	//Refresh table draw to get correct size (hiden on build)
        	$('#tableau').DataTable().draw();
        });
    
        //submission of the global form in Ajax, get the JSON answer 
        //and send it to download script via sendFromDownload javascript function
     	$('#download').on('submit', function(e) {
    			e.preventDefault();
    			var valide=checkForm(this);
    			if (valide){
    				$.ajax({
    					type: "POST",
    					dataType: "json",
    					url: "/download_manager",
    					data: $("#download").serialize(), // serializes the form's elements.
    					success: function(data) {	
    						sendFromDownload(data);
    
    					},
    					error: function(error){
    						console.log("Error:");
    						console.log(error);
    					}
    					
    				});
    
    			}
    
    	});
    
    	//SK VOIR COMMENT ENELVER LA METHODE PRIVEE
        //Listener to update table with deleted study
     	$('#includeDeleted').on('change', function(e) {
     		getData(this.checked);
    	});
    
    	//Get JSON form download_json api with callback to fill the datable
     	function getData(deleted) {
     		$.ajax({
    			type: "POST",
    			dataType: "json",
    			url: "scripts/get_series_json.php",
    			data: {'deleted' : deleted}, 
    			// serializes the form's elements.
    			success: handleData,
    			error: function(error){
    				console.log("Error:");
    				console.log(error);
    			}
    			
    		});
     	}
    
     	function handleData(data) {
    		var oTable = $('#tableau').DataTable();
    		oTable.clear().draw();
    		oTable.rows.add(data).draw();
     	}
    
        //Make datatables for manual selection
     	var table=$('#tableau').DataTable( {
     		sDom: 'Blrtip',
     		scrollX: true,
    		select: {
                style: 'os'
            }, 
            
            buttons: [
                {
                    text: 'Download',
                    action: function () {
                        var count = table.rows('.selected').count();
     					var rows=table.rows('.selected').data();
     					var orthancIDtoDownload=[];
     					//For each line get the Orthanc ID and add it to an array
     					if(count > 0){
    	 					for (var i = 0; i < count ; i++) {
    		 					var seriesArray=rows[i]['orthancSeriesIDs'];
    		 					for (var k in seriesArray) {
    		 						orthancIDtoDownload.push(seriesArray[k])
    	 						}
     						}
    	 					//Send the array to the function that create the form to send 
    		 				// the requested ID to the download script
    		                sendFromDownload(orthancIDtoDownload);
    					} else alertifyError('Select Row first');
     				   
                    }
                },
                {
                    text: 'Send To',
                    action: function () {
                        var count = table.rows('.selected').count();
     					var rows=table.rows('.selected').data();
     					var orthancIDtoDownload=[];
     					//For each line get the Orthanc ID and add it to an array
     					if(count > 0){
    	 					for (var i = 0; i < count ; i++) {
    	 						var seriesArray=rows[i]['orthancSeriesIDs'];
    		 					for (var k in seriesArray) {
    		 						orthancIDtoDownload.push(seriesArray[k])
    	 						}
     						}
    	 					$( "#json" ).val(JSON.stringify(orthancIDtoDownload));
    	 					$( "#peers" ).dialog('open');
    
    					} else alertifyError('Select Row first');
     				   
                    }
                }
            ],
            
    		data: getData(false),
            
    		columns: [
    			{ data: 'center' },
    			{ data: 'code' },
    			{ data: 'withdraw' },
    			{ data: 'visit_type' },
    			{ data: 'state_investigator_form' },
    			{ data: 'state_quality_control' },
    			{ data: 'nb_series' },
    			{ data: 'nb_instances' },
    			{ data: 'Disk_Size' },
    			{ data: 'orthancSeriesIDs' },
    		],
    		"columnDefs": [
    			{ "title": "Center", "targets": 0 },
    			{ "title": "Patient Number", "targets": 1 },
    			{ "title": "Withdraw", "targets": 2 },
    			{ "title": "Visit Name", "targets": 3 },
    			{ "title": "Investigation Form", "targets": 4 },
    			{ "title": "Quality Control", "targets": 5 },
    			{ "title": "Number of Series", "targets": 6 },
    			{ "title": "Number of Instances", "targets": 7 },
    			{ "title": "Disk_Size (MB)", "targets": 8 },
    			{ "title": "OrthancID","visible": false, "targets": 9 }
    			
    		],
    		"bSortCellsTop": true
    	} );
    
        //Search function in dataTable manual download
    	$( '#manual'  ).on( 'keyup', ".column_search" ,function () {
    
    		$('#tableau').DataTable()
    			.column( $(this).parent().index() )
    			.search( this.value )
    			.draw();
    		
    	});
    
    });
    
    // Create a form with the JSON id to download and send the request by post to the download script
    function sendFromDownload(data){
    
    	var pack = {json: data}
     	var form = document.createElement("form");
        form.setAttribute("method", "post");
        form.setAttribute("action", "scripts/download_dicom.php");
        var hiddenField = document.createElement("input");
        hiddenField.setAttribute("type", "hidden");
        hiddenField.setAttribute("name", "json");
        hiddenField.setAttribute("value", JSON.stringify(pack));
        form.appendChild(hiddenField);
    
        document.body.appendChild(form);
        form.submit();
        
    }

</script>

<div class="text-center">
	<input id="btn_general" class="btn btn-dark" type="button" value="General Download">
	<input id="btn_manual_download" class="btn btn-dark" type="button" value="Manual Selection">
</div><br>

<div id="general" style="display: none;">
				
    <form class="form-horizontal" id="download">
    
    <!-- Multiple Checkboxes -->
    <div class="form-group">
      <label class="control-label">Select Centers</label>
      <div class="col-md-4">
        <div class="checkbox">
        <label for="allcenters">
          <input type="checkbox" name="allcenters" id="allcenters" value="1">
          All Centers
        </label>
        </div>
        <?php
        $i=0;
		foreach ($centers as $centerCode=>$centerObject){
		?>
			<div class="checkbox">
			<label for="centers-<?=$i?>">
			<input type="checkbox" name="centers[]" id="centers-<?=$i?>" value="<?=$centerObject->code?>">
			<?=htmlspecialchars($centerObject->name)?>
			</label>
			</div>
		<?php
        $i++;
        }?>
      </div>
    </div>
    
    <!-- Multiple Checkboxes -->
    <div class="form-group">
      <label class="control-label" for="patients">Patients</label>
      <div class="col-md-2">
      <div class="checkbox">
      	<label for="allpatients">
          <input type="checkbox" name="allpatients" id="allpatients" value="1">
          All patients
        </label>
        <label for="patients-included">
          <input type="radio" name="patients" id="patients-included" value="Included">
          Only included
        </label>
        <label for="patients-withdrawn">
          <input type="radio" name="patients" id="patients-withdrawn" value="Withdrawn">
          Only withdrawn
        </label>
      </div>
      </div>
    </div>
    
    <!-- Multiple Checkboxes -->
    <div class="form-group">
      <label class="control-label" for="qc">Quality Control</label>
      <div class="col-md-4">
      <div class="checkbox">
        <label for="qc-0">
          <input type="radio" name="qc" id="qc-0" value="All" checked>
          All
        </label><br>
        <label for="qc-1">
          <input type="radio" name="qc" id="qc-1" value="Accepted">
          Only QC Accepted
        </label>
      </div>
      </div>
    </div>
    
    <div class="form-group">
      <label class="control-label" for="allvisits">Visits</label>
      <div class="col-md-4">
      <div class="checkbox">
        <label for="allvisits">
          <input type="checkbox" name="allvisits" id="allvisits" value="1">
          All Visits
        </label>
    	</div>
    
     <?php foreach($visits as $visit){
          $i++;
          ?>
          <div class="checkbox">
          <label for="visits-<?php echo($i)?>">
          <input type="checkbox" name="visits[]" id="visits-<?=$i?>" value="<?=htmlspecialchars($visit->name)?>">
          <?=htmlspecialchars($visit->name)?>
          </label>
          </div>
      <?php 
      }?>
      
      </div>
    </div>
    
    <button type="submit" name="confirmerDownload" class="btn btn-primary">Download</button>
    </form>

</div>

<div id="manual" style="display: none;">

	<input id="includeDeleted" class="form-check-input" type="checkbox">
	<label for="includeDeleted" class="form-check-label">View Deleted Series</label>
        
    <table class="table table-striped" id="tableau" style='text-align:center; width:100%'>
		<thead>
			<!-- Column name filled by dataTable -->
			<tr>
				<th></th>
				<th></th>
				<th></th>
				<th></th>
				<th></th>
				<th></th>
				<th></th>
				<th></th>
				<th></th>
			</tr>
			<!-- Search elements -->
			<tr>
				<th><input type="text" placeholder="Search"  class="column_search" style="max-width:75px"/></th>
				<th><input type="text" placeholder="Search"  class="column_search" style="max-width:75px"/></th>
				<th><input type="text" placeholder="Search"  class="column_search" style="max-width:75px"/></th>
				<th><input type="text" placeholder="Search"  class="column_search" style="max-width:75px"/></th>
				<th><input type="text" placeholder="Search"  class="column_search" style="max-width:75px"/></th>
				<th><input type="text" placeholder="Search"  class="column_search" style="max-width:75px"/></th>
   				<th></th>
				<th></th>
				<th></th>
			</tr>
		</thead>
	</table>
</div>
<!-- Peer send page, loaded in a Dialog -->
<div id="peers" style="display: none;">

	<script type="text/javascript"> 
	
	$(document).ready(function() {
		$('#sendPeer').click(function(e){
			//Ajax Request for sending
			$.ajax({
				type: "POST",
				dataType: "json",
				url: "scripts/send_to_peer.php",
				data: $("#formSendPeer").serialize(), // serializes the form's elements.
				success: function(data) {
					console.log(data);
					var i;
					for(i=0; i<data.length ; i++){
						//Add progress bar for this ID
						var id=data[i]['answer']['ID'];
						$('#formSendPeer').append('\
								<div class="progress">\
									<div class="progress-bar progress-bar-striped bg-info" id="progress'+id+'" role="progressbar" style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">\
									'+data[i]['username']+'</div>\
								</div>');
						
						addProgressMonitoring(id);
					}	
					

				},
				error: function(error){
					$('#progress-label').text("Error"+i+"/"+ids.length);
					console.log("Error:");
					console.log(error);
				}
				
			});
			
		});

		var addProgressMonitoring=function(id){
			$.ajax({
				type: "POST",
				dataType: "json",
				url: "scripts/get_job_progress.php",
				data: {jobId : id}, // serializes the form's elements.
				success: function(data) {	
					if(data['State']=="Failure"){
						$('#progress'+id).removeClass( "bg-info" ).addClass( "bg-danger" );
						$('#progress'+id).attr('aria-valuenow', '100%').css('width', '100%');
						return;
					}
					var progress=data['Progress'];
					$('#progress'+id).attr('aria-valuenow', progress+'%').css('width', progress+'%');
					//If not finished refresh at 3secs
					if(progress<100){
						setTimeout(function(){addProgressMonitoring(id)}, 3000);
					}

				},
				error: function(error){
					console.log("can't fetch progression");
				}
				
			});
		};
		
	});

</script>

	
    <form id="formSendPeer" class="text-center">
        <input type="hidden" name="json" id="json">
    	<SELECT name="selectedUsers[]" class="custom-select" size="7" style="width:auto;" multiple >
    	<?php 
        //on parcourt la BDD res_centre
    	foreach($usersInStudy as $user=>$roles) {
    	    echo ('<option value="'.htmlspecialchars($user).'">'.htmlspecialchars($user).'</option>');
        }
        ?>
    	</SELECT>
    	<button name="accept" id="sendPeer" type="button" class="btn btn-primary">Send</button>
    </form>

</div>
        