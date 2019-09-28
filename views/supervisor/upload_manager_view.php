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
    //Display and hide divs on button click
    function ouvrirFermer(button, id){
    	//Hide all div and remove class from all button
    	$('.upManagerDiv').hide();
    	$('.upManagerBtn').removeClass('btn-primary')
    	
    	//Show the selected div and select the calling button and force datable to draw (windows adjust)
    	$("#"+id).show();
    	$(button).addClass('btn-primary');
    	$('#table'+id).DataTable().columns.adjust().draw();	
    };
    
    $(document).ready(function() {
    
    	$( "#send_emails" ).dialog({
    		autoOpen: false,
    		width: 'auto',
    		height: 'auto',
    		close : function(){
    			tinymce.remove();
    		}
    	});
    	
    	$( "#send_emailsButton" ).on( "click", function() {
    		$( "#send_emails" ).load('/reminder_emails', function(){
    			$( "#send_emails" ).dialog( "open" );
    		});
    		
    	});
    	
    	<?php
        // For each visit create a dataTable
        foreach ($allVisits as $visit => $details) {
        ?>
    		$('#table<?=$visit?>').DataTable({
    				"sDom": 'Blrtip',
    				buttons: [ {
    					extend: 'collection',
    					text: 'Export',
    					buttons: [
    							'copy',
    							'excel',
    							'csv',
    							'pdf',
    							'print'
    							]
    					} ],
    					"orderCellsTop": true,
    					"scrollX": true
    				});
    
    		$( '#<?=$visit?>'  ).on( 'keyup', ".column_search" ,function () {
            	$('#table<?=$visit?>').DataTable()
                    .column( $(this).parent().index() )
                    .search( this.value )
                    .draw();
            });	
    	<?php
        }
        ?>
    
    });
</script>

<div class="text-center">
	<!-- Send Email Button -->
	<button id="send_emailsButton" type="button" class="btn btn-info">Send
		Reminder e-mails</button>
<?php
// Add 1 button per visit
foreach ($allVisits as $visit => $patients) {
    ?>
<button type="button"
		onclick="ouvrirFermer(this, '<?=$visit?>')"
		class="btn upManagerBtn"><?=$visit?></button>
<?php
}
?>
</div>
<br>
<div class="tab-content">
<?php
// Create Table for each visit
foreach ($allVisits as $visit => $patients) {
?>
    
    <div style="display:none" id=<?=$visit?> class="upManagerDiv">
		<table id="table<?=$visit?>" class="table table-striped" style="text-align:center; width:100%">
    		<thead>
        		<tr>
        		<th>Centre</th>
        		<th>Patient Number</th>
        		<th>Visit Status</th>
        		<th>Visit should be done after</th>
        		<th>Visit should be done before</th>
        		<th>upload Status</th>
        		<th>Acquisition date</th>
        		<th>Compliancy</th>
        		<th>Investigation form</th>
        		<th>Quality control</th>
        		</tr>
        		<tr>
        		<th><input type="text" placeholder="Search" class="column_search" style="max-width:75px" /></th>
        		<th><input type="text" placeholder="Search" class="column_search" style="max-width:75px" /></th>
        		<th><input type="text" placeholder="Search" class="column_search" style="max-width:75px" /></th>
        		<th><input type="text" placeholder="Search" class="column_search" style="max-width:75px"/> </th>
        		<th><input type="text" placeholder="Search" class="column_search" style="max-width:75px"/> </th>
        		<th><input type="text" placeholder="Search" class="column_search" style="max-width:75px"/> </th>
        		<th><input type="text" placeholder="Search" class="column_search" style="max-width:75px"/> </th>
        		<th><input type="text" placeholder="Search" class="column_search" style="max-width:75px"/> </th>
        		<th><input type="text" placeholder="Search" class="column_search" style="max-width:75px"/> </th>
        		<th><input type="text" placeholder="Search" class="column_search" style="max-width:75px"/> </th>
        		</tr>
    		</thead>
    		<tbody>
            <?php 
            foreach ($patients as $patientCode => $patientDetails) {
                // Table structure with search options
            ?>
                <tr>
            		<td><?=$patientDetails['center']?></td>
            		<td><?=$patientCode?></td>
            		<td><?=$patientDetails['status']?></td>
            		<td><?=$patientDetails['shouldBeDoneAfter']?></td>
            		<td><?=$patientDetails['shouldBeDoneBefore']?></td>
            		<td><?=$patientDetails['upload_status']?></td>
            		<td><?=$patientDetails['acquisition_date']?></td>
            		<td><?=$patientDetails['compliancy']?></td>
            		<td><?=$patientDetails['state_investigator_form']?></td>
            		<td><?=$patientDetails['state_quality_control']?></td>
        		</tr>
        	<?php
            }
            ?>
    		</tbody>
		</table>
	</div>
<?php 
}
?>
</div>

<!-- Div to load send email form will be displayed in dialog-->
<div id="send_emails"></div>
