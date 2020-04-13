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
        
        $( document ).ready(function() {

			<?php if ($role == User::INVESTIGATOR && $visitPossible) { ?>
				//Dialog for the add visit form
				$("#addVisit").dialog({
					autoOpen: false,
					modal : true,
					width : 'auto',
					height : 'auto',
					title: "Add new visit"
				});
				
				
				//load and open dialog when click on Add visit Button
				$('#createStudy').on('click', function(){
					$( "#addVisit" ).load('/new_visit',{
						patient_num : <?=$patient?>
					}, function(){
						$( "#addVisit" ).dialog('open');
					});
					
				});

			<?php 
			} ?>

			//Update Tree selection when click on Visit Name in patient Visit details
			$('#tab_visits').on('click', '.visitLink', function(event) {
				let visitId=$(this).attr('data-visitid')
				$('#containerTree').jstree("deselect_all")
				$('#containerTree').jstree(true).select_node(visitId)
			})
        
        });
        
        function refreshDivContenu(){
        	$('#contenu').load('/patient_interface', {
        			patient_num : <?=$patient?>
        	});
        	$('#containerTree').jstree(true).refresh();
        };


    
    </script>

<?php if ($role == User::INVESTIGATOR && $visitPossible) { ?>
    <div id="createVisitButton">
    	<button id='createStudy' class="btn btn-primary">New Visit</button>
    </div>
    
    <div id="addVisit"></div>
<?php 
}

//Add patient and visit table
build_patient_visit_table($patientObject);
build_visit_details_table($visitArray, $_SESSION['role']);
?>
