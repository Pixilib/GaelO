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
    
    	$( "#send_emails" ).dialog({
    		autoOpen: false,
    		width: 'auto',
    		height: 'auto',
    		close : function(){
    			tinymce.remove();
    		}
    	});

        function getVisitStatus(visitType, modality){

            $.ajax({
				type: "POST",
				dataType: "json",
				url: "scripts/get_patient_status.php",
				data: { visit_type : visitType, modality : modality  }, // serializes the form's elements.
				success: function(data) {
                    for (const [patientCode, details] of Object.entries(data)) {
                        tableStatus.row.add( [ modality,
                                                visitType,
                                                details.center, 
                                                patientCode, 
                                                details.status, 
                                                details.shouldBeDoneAfter, 
                                                details.shouldBeDoneBefore, 
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

        }

        $('.uploadManagerVisitBtn').on('click', function(){
            tableStatus.clear();
            $( this ).toggleClass( "btn-primary" )
            let selectedVisitButtons=$('.uploadManagerVisitBtn.btn-primary').get();
            
            selectedVisitButtons.forEach(button=>{
                let modality=button.dataset.modality
                let visitType=button.dataset.visitname
                getVisitStatus(visitType, modality)
            })

            tableStatus.draw();
 
        })
    	
    	$( ".reminderBtn" ).on( "click", function() {
            let reminderType=$(this).val()
            let rowsData=tableStatus.rows('.selected').data();

            rowsData.forEach(row=>{

            })
            
            $.ajax({
				type: "POST",
				dataType: "json",
				url: "scripts/send_emails_upload_reminder.php",
				data: { dataArray : rowsData, reminderType : reminderType }, // serializes the form's elements.
				success: function(data) {
                    console.log('ici')
				},
				error: function(error){
					console.log("can't send reminders");
				}
				
			});

    		
    	});
    	
        let tableStatus= $('#tableStatus').DataTable({
                "sDom": 'Blrtip',
                scrollX: true,
                select: {
                style: 'os'
                }, 
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
                                filename : '<?= $_SESSION['study']?>_Uploads_Manager_Export',
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
                                filename : '<?= $_SESSION['study']?>_Uploads_Manager_Export',
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
                                filename : '<?= $_SESSION['study']?>_Uploads_Manager_Export',
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
                            ]
                    } ],
                    "orderCellsTop": true,
                    "scrollX": true
                });

         //Search function in dataTable manual download
		$('.upManagerDiv').on('change keyup', ".column_search", function() {
			let searchValue = this.value
			let regex = false

			if($(this).prop("class").includes('select_search') && this.value != ""){
				searchValue = "^"+this.value+"$"
				regex = true
			}

            tableStatus.column( $(this).parent().index() )
                .search(searchValue, regex)
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
			foreach ($visitsName as $visitName) {
				?>
                <button type="button" data-modality=<?=$modality?> data-visitname="<?=$visitName?>" class="btn uploadManagerVisitBtn"><?=$visitName?> </button>
                <?php
			}
			echo('<br>');
		}
	?>
</div>
<br>

<div class="upManagerDiv">
    <table id="tableStatus" class="table table-striped" style="text-align:center; width:100%">
        <thead>
            <tr>
            <th>Modality</th>
            <th>Visit</th>
            <th>Center</th>
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
            <th><input type="text" placeholder="Search" class="column_search" style="max-width:75px" /></th>
            <th>
                <select type="text" placeholder="Search" class="column_search select_search" style="max-width:75px" >
                    <option value="">Choose</option>
                    <option value=<?=Visit::DONE?>><?=Visit::DONE?></option>	
                    <option value="<?=Patient_Visit_Manager::SHOULD_BE_DONE?>"><?=Patient_Visit_Manager::SHOULD_BE_DONE?></option>
                    <option value=<?=Patient_Visit_Manager::OPTIONAL_VISIT?>><?=Patient_Visit_Manager::OPTIONAL_VISIT?></option>
                    <option value=<?=Visit::NOT_DONE?>><?=Visit::NOT_DONE?></option>
                </select> 
            </th>
            <th><input type="text" placeholder="Search" class="column_search" style="max-width:75px"/> </th>
            <th><input type="text" placeholder="Search" class="column_search" style="max-width:75px"/> </th>
            <th>
                <select type="text" placeholder="Search" class="column_search select_search" style="max-width:75px" >
                    <option value="">Choose</option>
                    <option value=<?=Visit::DONE?>><?=Visit::DONE?></option>	
                    <option value=<?=Visit::NOT_DONE?>><?=Visit::NOT_DONE?></option>
                    <option value=<?=Visit::UPLOAD_PROCESSING?>><?=Visit::UPLOAD_PROCESSING?></option>
                </select> 
            </th>
            <th><input type="text" placeholder="Search" class="column_search" style="max-width:75px"/> </th>
            <th>
                <select type="text" placeholder="Search" class="column_search select_search" style="max-width:75px" >
                    <option value="">Choose</option>
                    <option value="Yes">Yes</option>	
                    <option value="No">No</option>
                </select> 
            </th>
            <th>
                <select type="text" placeholder="Search" class="column_search select_search" style="max-width:75px" >
                    <option value="">Choose</option>
                    <option value="<?=Visit::LOCAL_FORM_NOT_DONE?>"><?=Visit::LOCAL_FORM_NOT_DONE?></option>	
                    <option value="<?=Visit::LOCAL_FORM_DRAFT?>"><?=Visit::LOCAL_FORM_DRAFT?></option>
                    <option value="<?=Visit::LOCAL_FORM_DONE?>"><?=Visit::LOCAL_FORM_DONE?></option>
                </select> 
            </th>
            <th>
                <select type="text" placeholder="Search" class="column_search select_search" style="max-width:75px" >
                    <option value="">Choose</option>
                    <option value="<?=Visit::QC_NOT_DONE?>"><?=Visit::QC_NOT_DONE?></option>	
                    <option value="<?=Visit::QC_CORRECTIVE_ACTION_ASKED?>"><?=Visit::QC_CORRECTIVE_ACTION_ASKED?></option>
                    <option value="<?=Visit::QC_WAIT_DEFINITVE_CONCLUSION?>"><?=Visit::QC_WAIT_DEFINITVE_CONCLUSION?></option>
                    <option value="<?=Visit::QC_ACCEPTED?>"><?=Visit::QC_ACCEPTED?></option>
                    <option value="<?=Visit::QC_REFUSED?>"><?=Visit::QC_REFUSED?></option>
                </select> 
            </th>
            </tr>
        </thead>
        <tbody>
        </tbody>
    </table>
</div>

<div class="text-center" hidden>
    <span class="">Send Reminders : </span>
    <input type="button" class="btn btn-primary reminderBtn" value="Upload" />
    <input type="button" class="btn btn-primary reminderBtn" value="Investigator Form" />
    <input type="button" class="btn btn-primary reminderBtn" value="Corrective Action" />
</div>