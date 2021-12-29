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

<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="utf-8">

	<?php include_once $_SERVER['DOCUMENT_ROOT'].'/includes/jsLibrairies_main.php'; ?>

	<script type="text/javascript">
		$(document).ready(function() {

			$("#messengerDialog").dialog({
				autoOpen: false,
				width: 'auto',
				height: 'auto',
				close: function() {
					tinymce.remove();
				}
			});

			$("#messengerButton").on("click", function() {
				$("#messengerDialog").load('/messenger', function() {
					$("#messengerDialog").dialog("open");
				});
			});

			$("#myAccountButton").on("click", function() {
				$("#messengerDialog").load('/my_account', function() {
					$("#messengerDialog").dialog("open");
				});
			});

			

			$("#etude").on('change', function(e) {
				//flush Role selector
				$('#role').empty();
				var roleSelect = $('#role');
				//Repopulate Role selector by getting possible roles with Ajax
				$.ajax({
					type: "POST",
					dataType: 'json',
					url: "scripts/getRoles.php",
					data: {
						study: $("#etude").val()
					},
					success: function(data) {
						//For each answer add it in the Role list
						for (i = 0; i < data.length; i++) {
							roleSelect.append($('<option>', {
								value: data[i],
								text: data[i]
							}));

						}
						//if only one role possible select it
						if (roleSelect.children('option').length == 1) {
							$("#role").val($("#role option:first").val());
							$("#confirmStudyRole").click();
						}
					},
					error: function(error) {
						console.log("Error:");
						console.log(error);
					}

				});

			});

			//If only one study available select it automatically
			if ($("#etude").children('option').length == 1) {
				$("#etude").val($("#etude option:first").val());
			}

			//if not "choose" for refresh of role (usefull if browswer return is used)
			if ($("#etude").find(":selected").text() != "Choose") {
				$("#etude").change();
			}

			//Load content according to selected study / Role
			$("#confirmStudyRole").on('click', function(e) {
				var selectedRole = $("#role").val();
				var selectedStudy = $("#etude").val();

				//If supervisor load supervisor page
				if (selectedRole == "Supervisor") {
					$("#mainDiv").load('/root_supervisor', {
						etude: selectedStudy,
						role: selectedRole
					});

				}
				//If not supervisor, load investigator page
				else {
					$("#mainDiv").load('/root_investigator', {
						etude: selectedStudy,
						role: selectedRole
					});

				}

			});

			<?php if ($_SESSION['admin']) { ?>
				//Load admin interface on call
				$("#adminButton").on('click', function(e) {
					$("#mainDiv").load('/administrator');

				});
			<?php
			}
			?>


			//Handler button for logout
			$("#logOut").on('click', function(e) {
				location.href = "scripts/log_out.php";
			});

			//Listner to redirect ajaxLink class "Get" url to Ajax with reason and data in post
			//And refresh interface after action
			$("#mainDiv").on('click', ".ajaxLinkConfirm", function(e) {
				e.preventDefault();
				var url = this.href;
				var phpFile = url.slice(0, url.indexOf('?'));

				//Get the Get parameters
				var idVisit = getURLParameter(url, 'id_visit');
				var seriesOrthancId = getURLParameter(url, 'Series_Orthanc_Id');
				var studyOrthancId=getURLParameter(url, 'Study_Orthanc_Id'); 
				var actionType = getURLParameter(url, 'action');
				var idReview = getURLParameter(url, 'id_review');

				//Define refresh action according to origin location
				var refreshDiv;

				//If refresh concerne the supervisor interface
				if ($(this).hasClass('refreshVisitSupervisor')) {
					//If delete visit refresh all the supervisor
					if (url.includes('delete_visit.php')) {
						refreshDiv = function() {
							$("#confirmStudyRole").click();
						}
						//Else refresh the visit info
					} else {
						refreshDiv = function() {
							$("#supervisorDiv").load('/visit_infos', {
								id_visit: idVisit
							});
						}
					};
					//SK pas tres propre method divcontenu est definie dans patient et visit interface
				} else {
					//Case delete for investigator refresh tree after visit deletion
					if (url.includes('delete_visit.php')) {
						refreshDiv = function() {
							$('#contenu').empty();
							$('#containerTree').jstree(true).refresh();
						}
					}else{
						refreshDiv = function() {
							refreshDivContenu();

						}
					}

				}


				$('#reasonDiv').dialog({
					modal: true,
					buttons: {
						'OK': function() {
							var dialog = $(this);
							var reason = $('#reasonDelete').val();
							if (reason == '') {
								alertifyError('A reason must be specified');
							} else {
								$.ajax({
									type: "POST",
									dataType: 'json',
									url: phpFile,
									data: {
										id_visit: idVisit,
										seriesOrthancId: seriesOrthancId,
										studyOrthancId : studyOrthancId,
										action: actionType,
										idReview: idReview,
										reason: reason
									}, // serializes the form's elements.
									success: function(data) {
										if (data == true) {
											$('#reasonDelete').val('');
											dialog.dialog('close');
											alertifySuccess('Success');
											refreshDiv();
										} else {
											alertifyError('Error or Not Allowed');
										}

									},
									error: function(jqXHR, textStatus, errorThrown) {
										console.log("Error:");
									}
								});


							};

						},
						'Cancel': function() {
							$(this).dialog('close');
						}
					}
				});

			});

			//Listener to apply colors if page change in Datatables
			$(document).on('draw.dt', '.table', function() {
				changeColor();
			});

			//Listener to apply colors when a div is loaded
			$(document).bind("ajaxComplete", function() {
				changeColor();
			});
			
	    	

		});

		function getURLParameter(url, name) {
			return (RegExp(name + '=' + '(.+?)(&|$)').exec(url) || [, null])[1];
		}
	</script>
</head>

<body>
	<?php require $_SERVER['DOCUMENT_ROOT'].'/includes/header.php'; ?>

	<main class="container">
		<div class="block block-content">
			<div class="row" style="padding-bottom: 30px;">
				<!-- Study Selector -->
				<label>You are currently working on study:</label>
				<select class="custom-select" name="etude" id="etude" style="width:auto;">
					<?php
					//If multiple possibilities add Choose option
					if (sizeof($availableStudies) > 1) {
						echo '<option value="Choose">Choose</option>';
					}
					//Add all available studies in the selector
					foreach ($availableStudies as $study) {
						echo '<option value="'.htmlspecialchars($study).'">'.htmlspecialchars($study).'</option>';
					}
					?>
				</select>
				<label id="label_role">Role:</label>
				<select class="custom-select" name="role" id="role" style="width:auto;">
				</select>

				<button class="btn btn-success" id="confirmStudyRole">Confirm</button>
				<!-- Disconnection button -->
				<input class="btn btn-danger" type="button" id="logOut" value="Log out">

				<?php
				//If admin, add Admin button
				if ($_SESSION['admin']) {
					?>
					<input class="btn btn-danger" type="button" value="Admin" id="adminButton" style="margin-left: 20px;">
				<?php
				}
				?>			
				<div class="float-right">
					<button id="messengerButton" class="btn btn-info">Messenger</button>
					<button id="myAccountButton" class="btn btn-info mr-5">My Account</button>
				</div>

			</div>

			<div>
				<div id="messengerDialog"></div>
				<div id="mainDiv"> </div>
			</div>
			<div id="reasonDiv" style="display: none;">
				<form>Enter Reason<input type="text" name="reason" maxlength="255" id="reasonDelete"><br>
					<h1>Warning : Cannot Be Undone</h1>
				</form>
			</div>
		</div>
	</main>

	<footer class="page-footer font-small pt-4">
		<!-- Copyright -->
		<div class="text-center">
			GaelO <?= GAELO_VERSION ?>
		</div>
		<!-- Copyright -->

	</footer>

</body>

</html>