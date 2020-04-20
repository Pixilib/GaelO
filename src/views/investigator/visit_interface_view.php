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

// If Not done do not display anything
if ($visitObject->statusDone == Visit::NOT_DONE) {
	echo ("<h1>Visit Not Done</h1>");
	return;
} ?>

<script type="text/javascript">
    
	$(document).ready(function(){
    		$("#formInvestigator").load('/specific_form', {
    			id_visit : <?=$id_visit?>,
    			type_visit : '<?=$type_visit?>',
    			patient_num : <?=$patient_num?>
            });
            
        	//If upload validated display the detailed tables
            <?php
			if ($visitObject->uploadStatus == Visit::NOT_DONE && $role === User::INVESTIGATOR) {
			?>
                checkBrowserSupportDicomUpload('#uploadDicom');
               	new DicomUpload('#uploadDicom', {
                    expectedVisitsURL: '../../scripts/get_possible_import.php',
                    validationScriptURL: '../../scripts/validate_dicom_upload.php',
                    dicomsReceiptsScriptURL: '../../scripts/dicoms_receipts.php',
                    isNewStudyURL: '../../scripts/is_new_study.php',
                    callbackOnComplete: refreshDivContenu,
                    idVisit: <?= $id_visit ?? 'null' ?>
                }); 
           <?php
			}
			?>
          
			//If current user is controller diplay the quality controle form, the included QC form is disabled according to QC status
	       <?php
			if ($role == User::CONTROLLER && ($visitObject->stateQualityControl == Visit::QC_NOT_DONE || $visitObject->stateQualityControl == Visit::QC_WAIT_DEFINITVE_CONCLUSION)) {
				?>  
                $("#controlerForm").load('/controller_form', {
                    id_visit : <?= $id_visit ?>,
                    type_visit : '<?= $type_visit ?>',
                    patient_num : <?= $patient_num ?>
                });
    
    	       <?php
				// If investigator has reponded to the quality control, display the corrective action form
				if ($visitObject->stateQualityControl == Visit::QC_WAIT_DEFINITVE_CONCLUSION) {
				?>
            		$("#correctiveAction").load('/corrective_action', {
            			id_visit : <?= $id_visit ?>,
            			type_visit : '<?= $type_visit ?>',
            			patient_num : <?= $patient_num ?>
            		});
    	       <?php
				}
			}
    
			// If corrective action is asked, display the corrective action form for investigator (or monitor)
			if ($visitObject->stateQualityControl == Visit::QC_CORRECTIVE_ACTION_ASKED && ($role == User::INVESTIGATOR || $role == User::MONITOR)) {
				?>
            		$("#correctiveAction").load('/corrective_action', {
            			id_visit : <?=$id_visit?>,
            			type_visit : '<?=$type_visit?>',
            			patient_num : <?=$patient_num?>
            		});
            	
            		$("#controlerForm").load('/controller_form', {
            			id_visit : <?=$id_visit?>,
            			type_visit : '<?=$type_visit?>',
            			patient_num : <?=$patient_num?>
            		});
        
            	<?php
			}
			if ($role == User::REVIEWER || ($role == User::INVESTIGATOR && $visitObject->uploadStatus == Visit::DONE) || ($role == User::CONTROLLER && ($visitObject->stateQualityControl == Visit::QC_NOT_DONE || $visitObject->stateQualityControl == Visit::QC_WAIT_DEFINITVE_CONCLUSION))) {
				?>
            		$("#reviewerDownloadDicomBtn").on('click', function() {
            			$("#downloadForm").submit();
            		});
            		
            		$("#OHIFViewer").on('click', function() {
            			var win = window.open('/ohif/viewer/<?=$visitObject->getStudyDicomDetails()->studyUID ?>', '_blank');
                        if (win) {
                            //Browser has allowed it to be opened
                            win.focus();
                        } else {
                            //Browser has blocked it
                            alert('Please allow popups for this website');
                        }
            		});
        	    <?php
			}
			?>

	});
	
	function refreshDivContenu(){
		$('#contenu').load('/visit_interface', {
				id_visit : <?=$id_visit?>,
				type_visit : '<?=$type_visit?>',
				patient_num : <?=$patient_num?>
		});
		$('#containerTree').jstree(true).refresh();
	};

	<?php if ($visitObject->uploadStatus == Visit::UPLOAD_PROCESSING) {
		
		?>
	    function refreshUploadStatus(){
	        $.ajax({
	            type: "POST",
	            url: '/scripts/get_upload_status.php',
				dataType: 'json',
				//Do not trigger event to avoid conflit with dicomupload listener in parallel
				global: false,
	            data: {id_visit:<?=$id_visit?>},
				success: function(data) {
		           //Update the span of the upload status
					var spinner=$('#spinnerUploadDiv<?=$visitObject->id_visit?>');
					var uploadStatusLabel=$('#uploadStatusLabel<?=$visitObject->id_visit?>');
					var checkIcon=$('#checkIconDiv<?=$visitObject->id_visit?>');
					
					uploadStatusLabel.html(data);
					
		        	if(data == "<?=Visit::UPLOAD_PROCESSING?>"){
		        		spinner.show();
		        		setTimeout(refreshUploadStatus, 5000);
		        	}else if(data=="<?= Visit::DONE ?>"){
		        		checkIcon.show();
		        		uploadStatusLabel.css("color", "green");
		        		spinner.hide();
		        		<?php if ($visitObject->stateInvestigatorForm == Visit::DONE) {
							?>
		        			refreshDivContenu();
		        			<?php
						}?>
		        	}else{
		        		spinner.hide();
		        		uploadStatusLabel.css("color", "red");
		        		checkIcon.hide();
		        	}
	           },
	           error : function(){
		           console.log('error');
	           }
			});		

		}

	    $( document ).ready(function() {
	    	refreshUploadStatus();
	    });
	

	<?php 
	    
	}?>

    	
    </script>

<?php
// Status reminder
if ($role != User::REVIEWER) {
	?>
    <div class="bloc_bordures text-center mt-3 mb-3">
		<label><b>Upload Status:</b> <span id="uploadStatusLabel<?=$visitObject->id_visit?>"><?=$visitObject->uploadStatus?> </span> </label>
    	<span id="spinnerUploadDiv<?=$visitObject->id_visit?>" class="spinner-border text-primary" role="status" style="display: none;">
			<span class="sr-only">Loading...</span>
		</span>
		<span id="checkIconDiv<?=$visitObject->id_visit?>" style="display: none;">
			<svg xmlns="http://www.w3.org/2000/svg" width="12" height="16" viewBox="0 0 12 16">
				<path fill-rule="evenodd" d="M12 5l-8 8-4-4 1.5-1.5L4 10l6.5-6.5L12 5z"/>
			</svg>
		</span>
		<br>
		<label><b>Quality Control Status:</b> <?=$visitObject->stateQualityControl?></label>
    </div>
    <?php
}
// If reviewer or controler with awaiting QC action add an invisible form and a button to make dicom zip dowload
if ($role == User::REVIEWER || ($role == User::INVESTIGATOR && $visitObject->uploadStatus == Visit::DONE) || ($role == User::CONTROLLER && ($visitObject->stateQualityControl == Visit::QC_WAIT_DEFINITVE_CONCLUSION || $visitObject->stateQualityControl == Visit::QC_NOT_DONE))) {
	?>
    <div class="text-center mt-3 mb-3">
    	<input class="btn btn-primary" type="button"
    		id="reviewerDownloadDicomBtn" value="Download DICOM">
		<input class="btn btn-primary" type="button"
    		id="OHIFViewer" value="OHIF Viewer">
    </div>
    <form id="downloadForm" method="post"
    	action="scripts/download_dicom.php">
    	<input type="hidden" name="id_visit" value="<?=$id_visit?>">
    
    </form>

<?php
}
// If upload validated display the detailed tables
if ($visitObject->uploadStatus == Visit::DONE && $role != User::REVIEWER) {
	build_patient_visit_table($patientObject);
	build_visit_details_table(array($visitObject), $_SESSION['role']);
	build_table_series($role, $visitObject);
}
?>

<div id="uploadDicom" class="mt-3"></div>
<div id="correctiveAction"></div>
<div id="controlerForm"></div>
<div id="formInvestigator"></div>

<?php
function replaceEmpty($data) {
	if (empty($data)) {
		return ('/');
	}else {
		return (htmlspecialchars($data));
	}
}
/**
 * Generate Table of series details of a given visit for visit investigator interface
 */
function build_table_series($role, $visitObject)
{
	// Get Series Object Array with details
	$data_series=$visitObject->getSeriesDetails();
    
	$series_number=count($data_series);
    
	if ($series_number == 0) return;
    
	$colspan=$series_number+1;
	?>
	<div style="overflow-x:auto;"> 
		<table id="tab_series" class="table table-striped">
			<tr>
				<th colspan=<?=$colspan ?>>Series information</th>
			</tr>
			<tr>
				<td>Series Number</td>
    			<?php 
				for ($i=0; $i < $series_number; $i++) {
					if (empty($data_series[$i]->seriesNumber))
						?>
                        <td> 
                        	<?=replaceEmpty($data_series[$i]->seriesNumber)?>
                        </td>
            	<?php 
				}?>
    		</tr>
			<tr>
    			<td>Manufacturer</td>
    			<?php 
				for ($i=0; $i < $series_number; $i++) {
					?>
                    <td>
                    	<?=replaceEmpty($data_series[$i]->manufacturer)?>
                	</td>
               <?php 
				}?>
   			</tr>
			<tr>
				<td>Series Description</td>
				<?php 
				for ($i=0; $i < $series_number; $i++) {
					?>
                    <td>
                    	<?=replaceEmpty($data_series[$i]->seriesDescription)?>
                	</td>
               <?php 
				}
				?>
			</tr>
			<tr>
				<td>Modality</td>
				<?php 
				for ($i=0; $i < $series_number; $i++) {
					?>
                    <td>
                    	<?=replaceEmpty($data_series[$i]->modality)?>
                	</td>
               <?php 
				}
				?>
			</tr>
			<tr>
				<td>Acquisition Date Time</td>
				<?php 
				for ($i=0; $i < $series_number; $i++) {
					?>
                    <td>
                    	<?=replaceEmpty($data_series[$i]->acquisitionDateTime)?>
                	</td>
               <?php 
				}
				?>
   			</tr>
			<tr>
				<td>Total Dose (MBq)</td>
				<?php 
				for ($i=0; $i < $series_number; $i++) {
					?>
                    <td>
                    <?php if (empty($data_series[$i]->injectedDose)) {
						echo('/');
					}else {
						echo(htmlspecialchars($data_series[$i]->injectedDose/10 ** 6));
					}
					?>
                    </td>
                <?php
				}
				?>
			</tr>
			<tr>
				<td>Radiopharmaceutical</td>
				<?php 
				for ($i=0; $i < $series_number; $i++) {
					?>
                    <td>
                    	<?=replaceEmpty($data_series[$i]->radiopharmaceutical)?>
                	</td>
               <?php 
				}
				?>
			</tr>
			<tr>
				<td>Injection Date Time</td>
				<?php 
				for ($i=0; $i < $series_number; $i++) {
					?>
                    <td>
                    	<?=replaceEmpty($data_series[$i]->injectedDateTime)?>
                	</td>
               <?php 
				}
				?>
			</tr>
			<tr>
				<td>Radiopharm. Specific Activity (MBq)</td>
				<?php 
				for ($i=0; $i < $series_number; $i++) {
					?>
                    <td>
                    <?php if (empty($data_series[$i]->injectedActivity)) {
						echo('/');
					}else {
						echo(htmlspecialchars($data_series[$i]->injectedActivity/10 ** 6));
					}
					?>
                    </td>
                <?php
				}
				?>
			</tr>
			<tr>
				<td>Half Life (s)</td>
				<?php 
				for ($i=0; $i < $series_number; $i++) {
					?>
                    <td>
                    	<?=replaceEmpty($data_series[$i]->halfLife)?>
                	</td>
				<?php 
				}
				?>
			</tr>
			<tr>
				<td>Patient's Weight (kg)</td>
				<?php 
				for ($i=0; $i < $series_number; $i++) {
					?>
                    <td>
                    	<?=replaceEmpty($data_series[$i]->patientWeight)?>
                	</td>
				<?php 
				}
				?>
			</tr>
			<tr>
				<td>Slice Count</td>
				<?php 
				for ($i=0; $i < $series_number; $i++) {
					?>
                    <td>
                    	<?=replaceEmpty($data_series[$i]->numberInstances)?>
                	</td>
				<?php 
				}
				?>
			</tr>
			<tr>
				<td>Upload Date</td>
				<?php 
				for ($i=0; $i < $series_number; $i++) {
					?>
                    <td>
                    	<?=replaceEmpty($data_series[$i]->studyDetailsObject->uploadDate)?>
                	</td>
				<?php 
				}
				?>
			</tr>
			<?php 
			if (($role == User::INVESTIGATOR || $role == User::CONTROLLER) && ($visitObject->stateQualityControl != Visit::QC_ACCEPTED && $visitObject->stateQualityControl != Visit::QC_REFUSED)) {
			?><tr>
    	        <td>Delete</td>
            <?php 
			for ($i=0; $i < $series_number; $i++) {
				?>
                <td>
                	<a href=scripts/change_series_deletion.php?action=delete&Series_Orthanc_Id=<?=htmlspecialchars($data_series[$i]->seriesOrthancID)?> class="ajaxLinkConfirm" ><input class="btn btn-danger" type="button" value="Delete Series"></a>
            	</td>
        	<?php 
			}
			?>
           	</tr>
            <tr>
            	<td colspan="<?=$colspan?>">
            		<a href=scripts/change_series_deletion.php?action=delete&Series_Orthanc_Id=allVisit<?=$visitObject->id_visit?> class="ajaxLinkConfirm" ><input class="btn btn-danger" type="button" value="Delete All (Reset Upload)"></a>
        		</td>
    		</tr>
    	<?php 
		}?>
        </table> 
    </div>
<?php 
}
?>