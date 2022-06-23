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

<script>
	$(document).ready(function() {

		$("#editDateButton").on('click', function() {
			$("#update_visit_date").removeAttr('hidden');
		})

		//Dialog to set patient withdrawn
		$("#update_visit_date_btn").on('click', function() {

			if ($('#visit_date').val() == "") {
				alertifyError('Please choose date');
				return
			}

			if ($('#visit_date_reason').val() == "") {
				alertifyError('Please fill reason');
				return
			}

			$.ajax({
				type: "POST",
				url: '/scripts/update_visit_date.php',
				data: {
					visit_id: <?= $id_visit ?>,
					visit_date: $('#visit_date').val(),
					reason: $('#visit_date_reason').val()
				},
				success: function(data) {
					$('#visit_date_reason').val('')
					alertifySuccess('Update Done')
					linkVisitInfos(<?= $id_visit ?>)
				},
				error: function(jqXHR, textStatus, errorThrown) {
					console.log("Error:" + jqXHR);
					console.log(textStatus);
					console.log(errorThrown);

				}
			});

		});

		$('#datePickerVisitDate').datepicker({
			toggleActive: true,
			format: "yyyy-mm-dd"
		});

		$('#datePickerVisitDate').datepicker().on('changeDate', function(e) {
			let formattedDate = $('#datePickerVisitDate').datepicker("getFormattedDate")
			$('#visit_date').val(formattedDate);
		});

		//Display the contoller form
		<?php if ($visitObject->stateQualityControl != Visit::QC_NOT_DONE) { ?>
			$("#quality_control_form").load('/controller_form', {
				id_visit: <?= $id_visit ?>,
				type_visit: '<?= $visit_type ?>',
				patient_num: <?= $patientNumber ?>
			});
		<?php
		}

		//If a corrective action has been made and QC not concluded, display the corrective action form
		if (
			$visitObject->stateQualityControl != Visit::QC_ACCEPTED && $visitObject->stateQualityControl != Visit::QC_REFUSED
			&& $visitObject->correctiveActionDate != null
		) {
		?>
			$("#corrective_action_form").load('/corrective_action', {
				id_visit: <?= $id_visit ?>,
				type_visit: '<?= $visit_type ?>',
				patient_num: <?= $patientNumber ?>
			});
		<?php
		}

		if ($visitObject->stateInvestigatorForm != Visit::LOCAL_FORM_NOT_DONE) {
		?>
			//Display the local investigator specific form
			$("#investigatorForm").load('/specific_form', {
				id_visit: <?= $id_visit ?>,
				type_visit: '<?= $visit_type ?>',
				patient_num: <?= $patientNumber ?>,
				getLocal: true
			})
		<?php
		} ?>
		//Show / hide div on element click in visit table
		$(".visitDetails").on('click', function() {
			$('.visitInfoDiv').hide();
			var title = $(this).attr('title');
			$('#' + title).show();
			$.fn.dataTable
				.tables({
					visible: true,
					api: true
				})
				.columns.adjust();

		})

		$(".visitDetails").css('text-decoration', 'underline');

		$('.visitHistory').DataTable({
			"sDom": 'lrtip',
			"bSortCellsTop": true,
			"scrollX": true
		});

	});
</script>

<div class="text-center">
	<h1>Patient <?= $patientNumber ?> - <?= htmlspecialchars($visit_type) ?></h1>
</div>


<div>
	<?= make_interface_tableau_visites_supervisor($visitObject) ?>
</div>

<div id="visit" class="visitInfoDiv" style="display:none">
	<table class="table table-striped">
		<tr>
			<th>Created by</th>
			<td><?= htmlspecialchars($visitObject->creatorName) ?></td>
		</tr>
		<tr>
			<th>Creation date</th>
			<td><?= $visitObject->creationDate ?></td>
		</tr>
		<tr>
			<th>Visit Acquisition Date</th>
			<td><?= htmlspecialchars($visitObject->acquisitionDate) ?></td>
		</tr>
		<tr>
			<th>If Not done, Reason</th>
			<td><?= htmlspecialchars($visitObject->reasonForNotDone) ?></td>
		</tr>
	</table>
	<input class='btn btn-warning' id="editDateButton" type="button" value='Edit Acquisition Date'><br>

	<div id="update_visit_date" hidden>
			<div id="datePickerVisitDate"></div>
			<input name="visit_date" id="visit_date" type="hidden">
			Reason : <input  name="visit_date_reason" id="visit_date_reason" type="text">
			<input class="btn btn-warning" name="update_visit_date_btn" id="update_visit_date_btn" type="button" value="update">
	</div>
	<a href=scripts/delete_visit.php?id_visit=<?= $id_visit ?> class="ajaxLinkConfirm refreshVisitSupervisor"><input class='btn btn-danger' type="button" value='Delete Visit'></a><br>
	<?php makeHistoryTable("Visit", $trackerVisitResponses); ?>
</div>

<div id="serie" class="visitInfoDiv" style="display:none">
	<table class="table table-striped ">
		<tr>
			<th>Uploaded by</th>
			<td><?= htmlspecialchars($visitObject->uploaderUsername) ?></td>
		</tr>
		<tr>
			<th>Upload date</th>
			<td><?= $visitObject->uploadDate ?></td>
		</tr>
	</table>
	<?php
	echo (make_study_table($visitObject));
	makeHistoryTable("Serie", $trackerVisitResponses);
	?>
</div>

<div id="quality" class="visitInfoDiv" style="display:none">
	<div>
		<table class="table table-striped">
			<tr>
				<th>Quality Controle Done by</th>
				<td><?= htmlspecialchars($visitObject->controllerUsername) ?></td>
			</tr>
			<tr>
				<th>date</th>
				<td><?= $visitObject->controlDate ?></td>
			</tr>
		</table>

		<div id="quality_control_form"></div>
		<?php
		if ($visitObject->reviewStatus == Visit::NOT_DONE) {
		?>
			<a href=scripts/reset_qc.php?id_visit=<?= $id_visit ?> class="ajaxLinkConfirm refreshVisitSupervisor"><input class='btn btn-danger' type="button" value='Reset QC'></a>
		<?php
		} ?>

	</div>
	<br>
	<?php
	//If QC not finalized and existe corrective action data, display the user's corrective action data
	if ($visitObject->stateQualityControl != Visit::QC_ACCEPTED && $visitObject->stateQualityControl != Visit::QC_REFUSED && $visitObject->correctiveActionDate != null) {
	?>
		<table class="table table-striped">
			<tr>
				<th>Corrective Action Done by</th>
				<td><?= $visitObject->correctiveActionUsername ?></td>
			</tr>
			<tr>
				<th>date</th>
				<td><?= $visitObject->correctiveActionDate ?></td>
			</tr>
		</table>
	<?php
	}
	?>
	<div id="corrective_action_form"></div>

	<?php makeHistoryTable("Qc", $trackerVisitResponses); ?>
</div>

<div id="form" class="visitInfoDiv" style="display:none">

	<table class='table table-striped'>
		<tr>
			<th>Filled in by</th>
			<td><?php if (!empty($localReviewObject)) echo (htmlspecialchars($localReviewObject->username)); ?></td>
		</tr>
		<tr>
			<th>Date</th>
			<td><?php if (!empty($localReviewObject)) echo ($localReviewObject->reviewDate); ?></td>
		</tr>
	</table>

	<?php
	if (!empty($localReviewObject) && $visitObject->stateQualityControl != Visit::QC_ACCEPTED && $visitObject->stateQualityControl != Visit::QC_REFUSED) {
	?>
		<a href=scripts/unlock_form.php?id_review=<?= $localReviewObject->id_review . '&id_visit=' . $id_visit ?> class="ajaxLinkConfirm refreshVisitSupervisor"><input class='btn btn-danger' type="button" value='Unlock Form'></a>
		<a href=scripts/delete_form.php?id_review=<?= $localReviewObject->id_review . '&id_visit=' . $id_visit ?> class="ajaxLinkConfirm refreshVisitSupervisor"><input class='btn btn-danger' type=button value='Delete Form'></a>
	<?php
	} ?>
	<br>
	<div id="investigatorForm"></div>
	<?php makeHistoryTable("Form", $trackerVisitResponses, true); ?>
</div>


<div id="review_div" class="visitInfoDiv" style="display:none;">

	<table class='table table-striped'>
		<tr>
			<th>Conclusion Date</th>
			<td><?php if (!empty($visitObject->reviewConclusionDate)) echo ($visitObject->reviewConclusionDate);
				else echo ("N/A") ?></td>
		</tr>
		<tr>
			<th>Conclusion Value</th>
			<td><?php if (!empty($visitObject->reviewConclusion)) echo (htmlspecialchars($visitObject->reviewConclusion));
				else echo ("N/A") ?></td>
		</tr>
	</table>

	<?php make_interface_tableau_review_supervisor($data_reviews) ?>
	<?php makeHistoryTable("Form", $trackerVisitResponses, false); ?>
</div>

<?php
function makeHistoryTable($actionGroup, $trackerVisitResponses, bool $formInvestigator = null)
{

	if ($actionGroup == "Visit") {
		$actionArray = array("Create Visit", "Delete Visit", "Reactivate Visit");
	} else if ($actionGroup == "Form") {
		$actionArray = array("Save Form", "Unlock Form", "Delete Form");
	} else if ($actionGroup == "Qc") {
		$actionArray = array("Reset QC", "Quality Control", "Corrective Action");
	} else if ($actionGroup == "Serie") {
		$actionArray = array("Import Series", "Change Serie");
	}
?>
	<div class="mt-4" style="overflow-x:auto;">
		<h3>History</h3>
		<table class="table table-striped visitHistory block" style="width: 100%;">
			<thead>
				<tr>
					<td>Date</td>
					<td>Username</td>
					<td>Role</td>
					<td>Action Type</td>
					<td>Details</td>
				</tr>
			</thead>
			<tbody>
				<?php
				foreach ($trackerVisitResponses as $response) {

					if (in_array($response['action_type'], $actionArray)) {

						$details = json_decode($response['action_details'], true);

						if ($actionGroup == "Form") {
							if ($formInvestigator && $details['local_review'] == "0") continue (1);
							if (!$formInvestigator && $details['local_review'] == "1") continue (1);
						}

				?>
						<tr>
							<td><?= $response['date'] ?></td>
							<td><?= htmlspecialchars($response['username']) ?></td>
							<td><?= $response['role'] ?></td>
							<td><?= $response['action_type'] ?></td>
							<td><?= '<pre><code>' . json_encode($details, JSON_PRETTY_PRINT | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS) . '</code></pre>' ?></td>
						</tr>
				<?php
					}
				}
				?>
			</tbody>
		</table>
	</div>
<?php
}

function make_study_table($visitObject)
{

	$dicomStudies = $visitObject->getStudyDicomDetails();

	if (empty($dicomStudies)) {
		//If no available studies, show all deleted studies in the DB
		make_interface_tableau_study_supervisor($visitObject);
	} else {
		make_interface_tableau_series_supervisor($visitObject);
	}
}

/**
 * Display all deleted DICOM studies
 * @param Visit $visitObject
 */
function make_interface_tableau_study_supervisor(Visit $visitObject)
{
	$data_studies = $visitObject->getStudyDicomDetails(true);

	$studies_number = count($data_studies);

	//if no deleted studies do not display table at all
	if ($studies_number == 0) {
		return;
	}

?>
	<div style="overflow-x:auto;">
		<h2> Deleted Dicom Studies</h2>
		<table id="tab_series_super" class="table table-striped block">
			<tr>
				<th colspan=<?= ($studies_number + 1) ?>>Studies information</th>
			</tr>
			<tr>
				<td>Uploader</td>
				<?php
				for ($i = 0; $i < $studies_number; $i++) {
				?>
					<td><?= htmlspecialchars($data_studies[$i]->uploaderUsername) ?></td>
				<?php
				}
				?>
			</tr>
			<tr>
				<td>Upload Date</td>
				<?php
				for ($i = 0; $i < $studies_number; $i++) {
				?>
					<td><?= $data_studies[$i]->uploadDate ?></td>
				<?php
				}
				?>
			</tr>
			<tr>
				<td>Study Description</td>
				<?php
				for ($i = 0; $i < $studies_number; $i++) {
					if (empty($data_studies[$i]->studyDescription)) { ?>
						<td>/</td>
					<?php
					} else { ?>
						<td><?= htmlspecialchars($data_studies[$i]->studyDescription) ?></td>
				<?php
					}
				}
				?>
			</tr>
			<tr>
				<td>Acquisition Date Time</td>
				<?php
				for ($i = 0; $i < $studies_number; $i++) {
					if (empty($data_studies[$i]->studyAcquisitionDateTime)) { ?>
						<td>/</td>
					<?php
					} else { ?>
						<td><?= htmlspecialchars($data_studies[$i]->studyAcquisitionDateTime) ?></td>
				<?php
					}
				}
				?>
			</tr>
			<tr>
				<td>Nb Series / Nb Instances</td>
				<?php
				for ($i = 0; $i < $studies_number; $i++) {
				?>
					<td><?= $data_studies[$i]->nbOfSeries ?> / <?= $data_studies[$i]->numberOfInstances ?></td>
				<?php
				}
				?>
			</tr>

			<?php
			if ($visitObject->stateQualityControl != Visit::QC_ACCEPTED && $visitObject->stateQualityControl != Visit::QC_REFUSED) {
			?>
				<tr>
					<td></td>
					<?php
					for ($i = 0; $i < $studies_number; $i++) {
					?>
						<td>
							<a href='scripts/reactivate_study.php?Study_Orthanc_Id=<?= $data_studies[$i]->studyOrthancId ?>&id_visit=<?= $visitObject->id_visit ?>' class="ajaxLinkConfirm refreshVisitSupervisor"><input class="btn btn-danger" type="button" value="Reactivate Study"></a>
						</td>
				<?php
					}
				}
				?>
				</tr>
		</table>
	</div>
<?php
}

/**
 * Generate table series of the activated study Dicom
 * @param Visit $visitObject
 */
function make_interface_tableau_series_supervisor(Visit $visitObject)
{

	$data_series = $visitObject->getSeriesDetails(false);
	$deleted_series = $visitObject->getSeriesDetails(true);
	//Add deleted series at the end of array
	foreach ($deleted_series as $deleted_serie) {
		$data_series[] = $deleted_serie;
	}
	$series_number = count($data_series);
?>
	<div style="overflow-x:auto;">
		<table id="tab_series_super" class="table table-striped block">
			<tr>
				<th colspan=<?= ($series_number + 1) ?>>Series information</th>
			</tr>
			<tr>
				<td></td>
				<?php
				for ($i = 0; $i < $series_number; $i++) {
					if (empty($data_series[$i]->seriesNumber)) { ?>
						<td>/</td>
					<?php
					} else { ?>
						<td>Serie <?= htmlspecialchars($data_series[$i]->seriesNumber) ?></td>
				<?php
					}
				}
				?>
			</tr>
			<tr>
				<td>Series Description</td>
				<?php
				for ($i = 0; $i < $series_number; $i++) {
					if (empty($data_series[$i]->seriesDescription)) { ?>
						<td>/</td>
					<?php
					} else { ?>
						<td><?= htmlspecialchars($data_series[$i]->seriesDescription) ?></td>
				<?php
					}
				}
				?>
			</tr>
			<tr>
				<td>Manufacturer</td>
				<?php
				for ($i = 0; $i < $series_number; $i++) {
					if (empty($data_series[$i]->manufacturer)) { ?>
						<td>/</td>
					<?php
					} else { ?>
						<td><?= htmlspecialchars($data_series[$i]->manufacturer) ?></td>
				<?php
					}
				}
				?>
			</tr>
			<tr>
				<td>Modality</td>
				<?php
				for ($i = 0; $i < $series_number; $i++) {
					if (empty($data_series[$i]->modality)) { ?>
						<td>/</td>
					<?php
					} else { ?>
						<td><?= htmlspecialchars($data_series[$i]->modality) ?></td>
				<?php
					}
				}
				?>
			</tr>
			<tr>
				<td>Acquisition Date Time</td>
				<?php
				for ($i = 0; $i < $series_number; $i++) {
					if (empty($data_series[$i]->acquisitionDateTime)) { ?>
						<td>/</td>
					<?php
					} else { ?>
						<td><?= htmlspecialchars($data_series[$i]->acquisitionDateTime) ?></td>
				<?php
					}
				}
				?>
			</tr>
			<tr>
				<td>Injected Dose (MBq)</td>
				<?php
				for ($i = 0; $i < $series_number; $i++) {
					if (empty($data_series[$i]->injectedDose)) { ?>
						<td>/</td>
					<?php
					} else { ?>
						<td><?= htmlspecialchars(($data_series[$i]->injectedDose / 10 ** 6)) ?></td>
				<?php
					}
				}
				?>
			</tr>
			<tr>
				<td>Injection Date Time</td>
				<?php
				for ($i = 0; $i < $series_number; $i++) {
					if (empty($data_series[$i]->injectedDateTime)) { ?>
						<td>/</td>
					<?php
					} else { ?>
						<td><?= htmlspecialchars($data_series[$i]->injectedDateTime) ?></td>
				<?php
					}
				}
				?>
			</tr>
			<tr>
				<td>Radiopharm. Specific Activity (MBq)</td>
				<?php
				for ($i = 0; $i < $series_number; $i++) {
					if (empty($data_series[$i]->injectedActivity)) { ?>
						<td>/</td>
					<?php
					} else { ?>
						<td><?= htmlspecialchars(($data_series[$i]->injectedActivity / 10 ** 6)) ?></td>
				<?php
					}
				}
				?>
			</tr>
			<tr>
				<td>Patient's weight (kg)</td>
				<?php
				for ($i = 0; $i < $series_number; $i++) {
					if (empty($data_series[$i]->patientWeight)) { ?>
						<td>/</td>
					<?php
					} else { ?>
						<td><?= htmlspecialchars($data_series[$i]->patientWeight) ?></td>
				<?php
					}
				}
				?>
			</tr>
			<tr>
				<td>Slice count</td>
				<?php
				for ($i = 0; $i < $series_number; $i++) {
					if (empty($data_series[$i]->numberInstances)) { ?>
						<td>/</td>
					<?php
					} else { ?>
						<td><?= $data_series[$i]->numberInstances ?></td>
				<?php
					}
				}
				?>
			</tr>
			<tr>
				<td>Insert Date</td>
				<?php
				for ($i = 0; $i < $series_number; $i++) {
					if (empty($data_series[$i]->uploadDate)) { ?>
						<td>/</td>
					<?php
					} else { ?>
						<td><?= $data_series[$i]->uploadDate ?></td>
				<?php
					}
				}
				?>
			</tr>
			<?php
			if ($visitObject->stateQualityControl != Visit::QC_ACCEPTED && $visitObject->stateQualityControl != Visit::QC_REFUSED) {
			?>
				<tr>
					<td></td>
					<?php
					for ($i = 0; $i < $series_number; $i++) {
						$deleted = false;
						$action = "delete";
						if ($data_series[$i]->deleted) {
							$deleted = true;
							$action = "reactivate";
						}

					?>
						<td>
							<a href='scripts/change_series_deletion.php?action=<?= $action ?>&Series_Orthanc_Id=<?= $data_series[$i]->seriesOrthancID ?>&id_visit=<?= $visitObject->id_visit ?>' class="ajaxLinkConfirm refreshVisitSupervisor"><input class="btn btn-danger" type="button" value="<?= $action ?> Series"></a>
						</td>
				<?php
					}
				}
				?>
				</tr>
		</table>
	</div>
<?php
}


/**
 * Generate table with selected visit details for supervisor interface
 * @param $id_visit
 * @return string
 */
function make_interface_tableau_visites_supervisor($visitObject)
{
?>
	<div style="overflow-x:auto;">
		<table id='tab_visit_super' class='table table-striped block'>
			<tr>
				<th colspan=7>Visit information</th>
			</tr>
			<tr>
				<td rowspan=2>Visit</td>
				<td colspan=4>Visit status</td>
				<td rowspan=2>Review Status</td>
			</tr>
			<tr>
				<td>Visit</td>
				<td>Series upload</td>
				<td>Investigator form</td>
				<td>Quality control</td>
			</tr>

			<tr>
				<td><?= $visitObject->visitType ?></td>
				<td class="visitDetails" title="visit"><?= $visitObject->statusDone ?></td>
				<td class="visitDetails" title="serie"><?= $visitObject->uploadStatus ?></td>
				<td class="visitDetails" title="form"><?= $visitObject->stateInvestigatorForm ?></td>
				<td class="visitDetails" title="quality"><?= $visitObject->stateQualityControl ?></td>
				<td class="visitDetails" title="review_div"><?= $visitObject->reviewStatus ?></td>
			</tr>
		</table>
	</div>
<?php
}


function make_interface_tableau_review_supervisor($data_reviews)
{

	$res_nb_reviews = count($data_reviews);

	if ($res_nb_reviews == 0) {
		return;
	}
?>
	<div style="overflow-x:auto;">
		<table class="table table-striped block">
			<tr>
				<th></th>
				<?php
				for ($i = 1; $i <= $res_nb_reviews; $i++) {
				?>
					<th>Review <?= $i ?></th>
				<?php
				}
				?>
			</tr>
			<tr>
				<td>Name</td>
				<?php
				for ($i = 0; $i < $res_nb_reviews; $i++) {
				?>
					<td><?= $data_reviews[$i]->username ?>
						<?php if ($data_reviews[$i]->isLocal) echo ('- Local Investigator');
						if ($data_reviews[$i]->isAdjudication) echo ('- Adjudication Form');
						?>
					</td>
				<?php
				}
				?>
			</tr>
			<tr>
				<td>Reading Date</td>
				<?php
				for ($i = 0; $i < $res_nb_reviews; $i++) {
				?>
					<td><?= $data_reviews[$i]->reviewDate ?>
					</td>
				<?php
				}
				?>
			</tr>
			<tr>
				<td>Reading Form</td>
				<?php
				for ($i = 0; $i < $res_nb_reviews; $i++) {
					$dataSpecific = $data_reviews[$i]->getSpecificData();
				?>
					<td style="text-align:unset;">
						<pre><code><?= json_encode($dataSpecific, JSON_PRETTY_PRINT | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS) ?></code></pre>
					</td>
				<?php
				}
				?>
			</tr>
			<tr>
				<td>Associated Files</td>
				<?php
				
				for ($i = 0; $i < $res_nb_reviews; $i++) {
					$reviewObject = $data_reviews[$i];
					$associatedFiles = $reviewObject->associatedFiles;
					if(sizeof($associatedFiles) > 0){
						?>
						<td style="text-align:unset;">
							<div id="dropdown_dl_associated" style="display: inline-block">
								<button id="dropdownMenuButton"  class="btn btn-primary dropdown-toggle" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
									Files
								</button>
								<div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
									<?php
									
										foreach($associatedFiles as $filekey => $fileName){
											?>
											<a class="dropdown-item" href="scripts/get_review_attached_file_supervisor.php?id_review=<?= $reviewObject->id_review ?>&file_key=<?= $filekey ?>" value=<?= $filekey ?> > <?= $filekey ?> </a>
											<?php
										}
									?>
								</div>
							</div>
						</td>
					<?php
					} else {
						?>
							<td style="text-align:unset;">None</td>
						<?php
					}
				}
				?>
			</tr>
			<tr>
				<td>Status</td>
				<?php
				for ($i = 0; $i < $res_nb_reviews; $i++) {
				?>
					<td>
						<?php
						if ($data_reviews[$i]->validated) echo ('Validated');
						else echo ('Draft');
						?>
					</td>
				<?php
				}
				?>
			</tr>
			<tr>
				<td>Action</td>
				<?php
				for ($i = 0; $i < $res_nb_reviews; $i++) {
					if ($data_reviews[$i]->isLocal == 0) {
						//If validated show both unlock and delete button
						if ($data_reviews[$i]->validated) {
				?>
							<td>
								<a href="scripts/unlock_form.php?id_review=<?= $data_reviews[$i]->id_review ?>&id_visit=<?= $data_reviews[$i]->id_visit ?>" class="ajaxLinkConfirm refreshVisitSupervisor"><input class="btn btn-danger" type="button" value="Unlock Form"> </a>
								<a href="scripts/delete_form.php?id_review=<?= $data_reviews[$i]->id_review ?>&id_visit=<?= $data_reviews[$i]->id_visit ?>" class="ajaxLinkConfirm refreshVisitSupervisor"><input class="btn btn-danger" type="button" value="Delete Form"> </a>
							</td>
						<?php
							//if not validated (not locked) show only delete button
						} else {
						?>
							<td>
								<a href="scripts/delete_form.php?id_review=<?= $data_reviews[$i]->id_review ?>&id_visit=<?= $data_reviews[$i]->id_visit ?>" class="ajaxLinkConfirm refreshVisitSupervisor"><input class="btn btn-danger" type="button" value="Delete Form"> </a>
							</td>
						<?php
						}
					} else {
						?>
						<td></td>
				<?php
					}
				}
				?>
			</tr>
		</table>
	</div>
<?php
}
?>