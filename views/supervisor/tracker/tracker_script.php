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

<script>
	$('#trackerTableSupervisor').DataTable( {
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
				] }
			],
			"bSortCellsTop": true,
			"scrollX": true,
			"order": [[ 0, "desc" ]]
		});
	
	
	$( '#trackerDiv'  ).on( 'keyup', ".column_search" ,function () {
		$('#trackerTableSupervisor').DataTable()
			.column( $(this).parent().index()+':visIdx' )
			.search( this.value )
			.draw();
	});

	// Re-draw the table when the a date range filter changes
	$( '.date_range_filter'  ).on( 'change' ,function () {
		$('#trackerTableSupervisor').DataTable().draw();
	} );

		
	//Extend search function of datatable to filter date range
	//This will be aplied for all dataTable instance, so we add a condition
	//To apply only to "trakerTable"
	$.fn.dataTable.ext.search.push(
	    function( settings, searchData, index, rowData, counter ) {
		    var tableId=settings.oInstance[0].id;
		    //Apply only for tracker table
		    if(tableId.includes("trackerTableSupervisor")){

			    if(isEmptyRow(tableId,index)) return false;
			    
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

	$('.columnSelect').on( 'click', function (e) {
 
        // Get the column API object
        var column = $('#trackerTableSupervisor').DataTable().column( $(this).val() );
 
        // Toggle the visibility
        column.visible( $(this).is(':checked') );
        $('#trackerTableSupervisor').DataTable().draw();
    } );

	//Search if line is empty taking acount of hidden column (except date, username)
    function isEmptyRow(tableId, rowIndex){
    	var table=$('#'+tableId).DataTable();
    	var isEmptyLine=true;
    	for(i=2 ; i<table.columns().header().length; i++){
    		if(! table.column(i).visible()) continue;
			var data=table.cell(table.row(rowIndex), i).data();
			if (data){
				isEmptyLine=false;
				break;
			}

    	}
		return isEmptyLine;
    	
    }

	//Trigger popover on each draw event as the element appear in the DOM
    $('#trackerTableSupervisor').DataTable().on( 'draw', function () {
    	 $('.popover-dismiss').popover({
      		  trigger: 'focus'
		})
    } );

	//Start popover elements
    $( document ).ready(function() {
    	 $('.popover-dismiss').popover({
   		  trigger: 'focus'
   		})
    });
    	
   
	
</script>

<div id="dateFilter" class="text-center" >
		Date Filter : <input class="date_range_filter" id="date_from" type="date" style="max-width:150px"/>
       	-
        <input class="date_range_filter" id="date_to" type="date" style="max-width:150px" />
        <br> Server TimeZone : <?=date_default_timezone_get()?>
</div>   