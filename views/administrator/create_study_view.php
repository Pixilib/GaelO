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
        
        var visitNameArray=[];
        var visitOrderArray=[];
    	
        $('#deleteVisit').click(function(e) {
        	$("#visits option").remove();
        	$('#visitOrder').val(0);
        	//Clear Array storage
			visitNameArray=[];
			visitOrderArray=[];
        });


        
        $('#addVisit').click(function(e) {
            var visitName=$('#visitName').val();
            var visitOrder=$('#visitOrder').val();
            
            if( !visitNameArray.includes(visitName) && !visitOrderArray.includes(visitOrder) ) {
            	visitNameArray.push($('#visitName').val());
            	visitOrderArray.push($('#visitOrder').val());
            	$('#visits').append( $('<option>', {
            	    value: ''+$('#visitName').val()+'+Order='+$('#visitOrder').val()+'+NumDays='+$('#numberDay').val()+'',
            	    text: ''+$('#visitName').val()+' Order '+$('#visitOrder').val()+' Days '+$('#numberDay').val()+''
            	}));
            	//reset input fields
            	$('#visitName').val('');
            	$('#numberDay').val(0);
            	//increment counter
            	$('#visitOrder').val( +$('#visitOrder').val()+1);
        	}else{
        		alertifyError('Visit Name or Visit Order already defined');
        	}
        	
        });
       
        //Select all the visit select and send the form
        $("#createSubmit").on("click",function(eve){
            var existingStudies=<?=json_encode($allStudiesArray)?>
            //check form completion
        	var visitnb=$("#visits").find("option").length;
        	var studyName=$("#studyName").val();
        	if(studyName.length>0 && visitnb>0 && !existingStudies.includes(studyName) ){
            	$("#visits").find("option").prop("selected", true);
            	$("#createStudy").submit();
        	}else{
        		if( existingStudies.includes( $("#studyName").val() ) ){
        			alertifyError('Study Name identical to an existing study, choose a new one');
        		}else{
        			alertifyError('Study Name and at least one visit must be set');
        		}
            	
        	}
        })
        
    });
    
    //Check add visit name is unique in the selector
    function checkIsUniqueVisit(visitName) {
        var visitList=$('#visits')[0];
        var alreadyInList=false;
    
        for (i = 0; i < visitList.length; ++i ){
          if (visitList.options[i].text == visitName ){
        	  alertifyError("Already in list");
            alreadyInList=true;
          }
        }
        
        if (!alreadyInList){
    		return true;
        }else{
            return false;
        }   
    }
    
</script>
<div class="jumbotron">
    <form method="post" id="createStudy"
    	action="/create_study">
    	<div class="form-group row">
    		<label class="col-form-label">Study Name:</label> <input type="text"
    			class="form-control" id="studyName" name="studyName"
    			placeholder="study Name" maxlength="32" required>
    	</div>
    	<div class="form-group row">
    		<div class="form-group col">
    			<label class="col-form-label">Visits In Study :</label>
    			<!------------------------- Visit List ----------------------->
    			<SELECT id="visits" class="custom-select" name="visits[]" multiple>
    			</SELECT> <input id="deleteVisit" class="btn btn-danger" type="button"
    				value="Clear">
    		</div>
    		<div class="form-group col">
    			<label class="col-form-label">Visit Name:</label> 
    				<input type="text"
    				class="form-control" id="visitName" name="visitName" maxlength="32"
    				placeholder="visit Name"> 
    			<label class="col-form-label">Visit Order:</label> 
    				<input type="number"
    				class="form-control" id="visitOrder" name="visitOrder" min="0" value="0"
    				placeholder="visit Order"> 
				<label class="col-form-label">Number of Days Visit Limit:</label> 
    				<input class="form-control"
    				id="numberDay" name="numberDay" placeholder="days" type="number"
    				step="1" value=0> <input id="addVisit" class="btn btn-primary"
    				type="button" value="+">
    		</div>
    	</div>
    
    	<div class="form-group row">
			<label class="col-sm" for="localFormNeeded">Local Form Needed</label>
    		<input class="form-control col-sm" type="checkbox" name="localFormNeeded" value="1" checked>
    	</div>
    
    	<div class="form-group row">
    		<label class="col-sm" for="qcNeeded">QC Needed</label>
    		<input class="form-control col-sm" type="checkbox" name="qcNeeded" value="1" checked>
    	</div>
    	
		<div class="form-group row">
    		<label class="col-sm" for="reviewNeeded">Review Needed</label>
    		<input class="form-control col-sm" type="checkbox" name="reviewNeeded" value="1" checked>
    	</div>
    	
		<div class="form-group row">
    		<label class="col-sm" for="formQc">Days limit for first visit compared to inclusion</label>
    		<input class="form-control col-sm" type="number" name="daysLimitInclusion" step="1" value=-28>
    	</div>
    
    	<button type="button" id="createSubmit" class="btn btn-primary">Create
    		Study</button>
    
    </form>
</div>