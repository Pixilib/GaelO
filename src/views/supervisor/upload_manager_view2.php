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
    
    	$( "#send_emails" ).dialog({
    		autoOpen: false,
    		width: 'auto',
    		height: 'auto',
    		close : function(){
    			tinymce.remove();
    		}
    	});

        $('.uploadManagerVisitBtn').on('click', function(){
            let modality=$(this).data("modality");
            let visitType=$(this).data("visitname");
            $.ajax({
				type: "POST",
				dataType: "json",
				url: "scripts/get_patient_status.php",
				data: { visit_type : visitType, modality : modality  }, // serializes the form's elements.
				success: function(data) {
                    tableStatus.clear()
                    for (const [patientCode, details] of Object.entries(data)) {
                        tableStatus.row.add( [ details.center, 
                                                patientCode, 
                                                details.status, 
                                                details.shouldBeDoneAfter, 
                                                details.shouldBeDoneAfter, 
                                                details.upload_status, 
                                                details.acquisition_date,
                                                details.compliancy,
                                                details.state_investigator_form,
                                                details.state_quality_control ] )
                    }

                    tableStatus.draw();

				},
				error: function(error){
					console.log("can't fetch patient's status");
				}
				
			});
 
        })
    	
    	$( "#send_emailsButton" ).on( "click", function() {
    		$( "#send_emails" ).load('/reminder_emails', function(){
    			$( "#send_emails" ).dialog( "open" );
    		});
    		
    	});
    	
        let tableStatus= $('#tableStatus').DataTable({
                "sDom": 'Blrtip',
                buttons: [ {
                    extend: 'collection',
                    text: 'Export',
                    buttons: [
                            {
                                extend: 'copy',
                                exportOptions: {
                                    columns: ':visible',
                                    rows: ':visible'
                                }
                            },
                            {
                                extend: 'excel',
                                exportOptions: {
                                    columns: ':visible',
                                    rows: ':visible'
                                }
                            },
                            {
                                extend: 'csv',
                                exportOptions: {
                                    columns: ':visible',
                                    rows: ':visible'
                                }
                            },
                            {
                                extend: 'pdf',
                                exportOptions: {
                                    columns: ':visible',
                                    rows: ':visible'
                                }
                            },
                            {
                                extend: 'print',
                                exportOptions: {
                                    columns: ':visible',
                                    rows: ':visible'
                                }
                            }
                            ]
                    } ],
                    "orderCellsTop": true,
                    "scrollX": true
                });

        $( '#tableStatus'  ).on( 'keyup', ".column_search" ,function () {
            tableStatus.column( $(this).parent().index() )
                .search( this.value )
                .draw();
        });	

    
    });
</script>

<div class="text-center">
    <?php
        // Add 1 button per visit
        foreach ($allVisits as $modality => $visitsName) {
            ?>
            <span class="badge badge-info"><?=$modality?></span>
            <?php
            foreach($visitsName as $visitName){
                ?>
                <button type="button" data-modality=<?=$modality?> data-visitname="<?=$visitName?>" class="btn uploadManagerVisitBtn"><?=$visitName?> </button>
                <?php
            }
            echo('<br>');
        }
    ?>
</div>
<br>
<div class="tab-content">
    
    <div class="upManagerDiv">
		<table id="tableStatus" class="table table-striped" style="text-align:center; width:100%">
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
    		</tbody>
		</table>
	</div>
</div>