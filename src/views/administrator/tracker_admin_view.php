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
	$(document).ready(function() {

		$('#trackerTableAdministrator').DataTable( {
    		"sDom": 'Blrtip',
    		buttons: [ {
    				extend: 'collection',
    				text: 'Export',
    				buttons: [
					{
						extend: 'copy',
						exportOptions: {
							modifier : {
								order : 'index', // 'current', 'applied','index', 'original'
								page : 'all', // 'all', 'current'
								search : 'applied' // 'none', 'applied', 'removed'
							}
						}
					},
					{
						extend: 'excel',
						filename : 'GaelO_Tracker_Export',
						exportOptions: {
							modifier : {
								order : 'index', // 'current', 'applied','index', 'original'
								page : 'all', // 'all', 'current'
								search : 'applied' // 'none', 'applied', 'removed'
							}
						}
					},
					{
						extend: 'csv',
						filename : 'GaelO_Tracker_Export',
						exportOptions: {
							modifier : {
								order : 'index', // 'current', 'applied','index', 'original'
								page : 'all', // 'all', 'current'
								search : 'applied' // 'none', 'applied', 'removed'
							}
						}
					},
					{
						extend: 'pdf',
						filename : 'GaelO_Tracker_Export',
						exportOptions: {
							modifier : {
								order : 'index', // 'current', 'applied','index', 'original'
								page : 'all', // 'all', 'current'
								search : 'applied' // 'none', 'applied', 'removed'
							}
						}
					},
					{
						extend: 'print',
						exportOptions: {
							modifier : {
								order : 'index', // 'current', 'applied','index', 'original'
								page : 'all', // 'all', 'current'
								search : 'applied' // 'none', 'applied', 'removed'
							}
						}
					}
					] }
    			],
    		"bSortCellsTop": true,
    		"scrollX": true,
    		"order": [[ 0, "desc" ]]
		});
	
	
		$( '#trackerDivAdministrator' ).on( 'keyup', ".column_search" ,function () {
			$('#trackerTableAdministrator').DataTable()
				.column( $(this).parent().index() )
				.search( this.value )
				.draw();
		});

		// Re-draw the table when the a date range filter changes
		$( '.date_range_filter'  ).on( 'change' ,function () {
			$('#trackerTableAdministrator').DataTable().draw();
		} );
	
	
    	//Extend search function of datatable to filter date range
    	//This will be aplied for all dataTable instance, so we add a condition
    	//To apply only to "trakerTableAdministrator"
    	$.fn.dataTable.ext.search.push(
    	    function( settings, searchData, index, rowData, counter ) {
    		    var tableId=settings.oInstance[0].id;
    		    //Apply only for tracker table
    		    if(tableId.includes("trackerTableAdministrator")){
    				var minDate  = $('#date_from').val();
    		        if(minDate == "") minDate="1900-01-01";
    		        minDate=minDate+" 00:00:00";
    		        var maxDate  = $('#date_to').val();
    		        if(maxDate == "") maxDate="2100-01-01";
    		        maxDate=maxDate+" 23:59:59";
    		       
    		        var createdAt = searchData[0]; // Our date column in the table
    		        var momentCreatedAt=(moment(createdAt, "YYYY-MM-DD HH:mm:ss"));
    		        var momentMin=(moment(minDate, "YYYY-MM-DD HH:mm:ss"));
    		        var momentMax=(moment(maxDate, "YYYY-MM-DD HH:mm:ss"));
    
    		        if  ( momentCreatedAt.isBetween(momentMin, momentMax) ) {
    		            return true;
    		        }
    		        return false;
            	//If not tracker table always return true to disable filter date
    		    }else{
    			    return true;
    		    }
    	    }
    	);
	});


</script>
	
<div id="trackerAdmin" >
	<br>
	<div id="dateFilter" class="text-center" >
			Date Filter : <input class="date_range_filter" id="date_from" type="date" style="max-width:150px"/>
           	-
            <input class="date_range_filter" id="date_to" type="date" style="max-width:150px" />
   </div>     
    
    <div id="trackerDivAdministrator" class="trackerRoleDiv">
    	<h1>Administrator Log</h1>
        <table id="trackerTableAdministrator" class="table table-borderless table-striped" style="text-align:center; width:100%">
            <thead>
                <tr>
                <th>Date</th>
                <th>Username</th>
                <th>Account Creation</th>
                <th>Account Modification</th>
                <th>Centers Change</th>
                <th>Create Study</th>
                <th>Change Study Activation</th>
                </tr>
                <tr>
                <th><input type="text" placeholder="Search" class="column_search" style="max-width:75px" /></th>
                <th><input type="text" placeholder="Search" class="column_search" style="max-width:75px" /></th>
                <th><input type="text" placeholder="Search" class="column_search" style="max-width:75px" /></th>
                <th><input type="text" placeholder="Search" class="column_search" style="max-width:75px" /></th>
                <th><input type="text" placeholder="Search" class="column_search" style="max-width:75px" /></th>
                <th><input type="text" placeholder="Search" class="column_search" style="max-width:75px" /></th>
                <th><input type="text" placeholder="Search" class="column_search" style="max-width:75px" /></th>
                </tr>
            
            </thead>
            <tbody>
            <?php 
			//Specific query because administrator log are not study defined
			foreach ($trackerAdministrator as $administratorEvent) {
				$eventDetails=json_decode($administratorEvent['action_details'], true);
			?>
            	<tr>
					<td><?php echo($administratorEvent['date']); ?></td>
					<td><?php echo($administratorEvent['username']); ?></td>
					<td>
					<?php 
					if ($administratorEvent['action_type'] == "Create User") echo('<pre><code>'.json_encode($eventDetails, JSON_PRETTY_PRINT|JSON_HEX_QUOT|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS).'</code></pre>');
					?>
					</td>
					<td>
					<?php 
					if ($administratorEvent['action_type'] == "Edit User") echo('<pre><code>'.json_encode($eventDetails, JSON_PRETTY_PRINT|JSON_HEX_QUOT|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS).'</code></pre>');
					?>
					</td>
					<td> 
					<?php 
					if ($administratorEvent['action_type'] == "Change Center") echo('<pre><code>'.json_encode($eventDetails, JSON_PRETTY_PRINT|JSON_HEX_QUOT|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS).'</code></pre>');
					?>
					</td>
					<td> 
					<?php 
					if ($administratorEvent['action_type'] == "Create Study") echo('<pre><code>'.json_encode($eventDetails, JSON_PRETTY_PRINT|JSON_HEX_QUOT|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS).'</code></pre>');
					?>
					</td>
					<td> 
					<?php 
					if ($administratorEvent['action_type'] == "Change Study Activation") echo('<pre><code>'.json_encode($eventDetails, JSON_PRETTY_PRINT|JSON_HEX_QUOT|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS).'</code></pre>');
					?>
					</td>

				</tr>
			<?php 
			}
			?>
            </tbody>
        </table>
    </div>
</div>