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

		window.refreshInvestigatorDiv = function(){
			$('#role').val('Investigator');
			$('#confirmStudyRole').click();
		}

		$("#documentationButton").on("click", function() {
			$("#investigatorDialog").load('/documentation', function() {
				$("#investigatorDialog").dialog("open");
				$("#investigatorDialog").dialog('option', 'title', "Documentation of study : <?= htmlspecialchars($_SESSION['study']) ?>");
			});
		});

		<?php
		if ($_SESSION['role'] == User::INVESTIGATOR) {
			?>

		$("#uploadApp").on("click", function() {

			if($("#uploadApp").text()=="Multi Uploader"){

				$("#tree").html('<div id="uploadDicom" style="width:100%"></div>');
				checkBrowserSupportDicomUpload('#uploadDicom');
				new DicomUpload('#uploadDicom', {
					multiImportMode: true,
					expectedVisitsURL: '../../scripts/get_possible_import.php',
					validationScriptURL: '../../scripts/validate_dicom_upload.php',
					dicomsReceiptsScriptURL: '../../scripts/dicoms_receipts.php'
				});

				$("#uploadApp").html("Exit Uploader");
				$("#uploadApp").removeClass("btn-dark").addClass("btn-warning");

			}else{

				$("#uploadApp").html("Multi Uploader");
				$("#uploadApp").removeClass("btn-warning").addClass("btn-dark");
				refreshInvestigatorDiv();
			}
			
		});
		<?php
		}
		?>

		//Build Tree by Getting JSON structure in Ajax
		$('#containerTree').jstree({
			"core": {
				'data': {
					'global' : false,
					'url': 'scripts/getTree.php',
					'dataType': 'json',
					"type": "GET"
				},
				'dblclick_toggle': false,
				'check_callback': true
			},
			"plugins": ["search", /*"contextmenu",*/ "state"],
			"search": {
				"case_sensitive": false,
				"show_only_matches": true,
				"show_only_matches_children" : true
			}/*,
			//SK To Evaluate in the Future
			"contextmenu": {
				"items": function($node) {
					return {
						"Show": {
							"label": "Remove Concluded",
							"action": function(obj) {
								$($('#containerTree').jstree(true).get_json('#', {
									flat: true
								})).each(function(index, value) {
									//Look the visit level
									if (value.parent != '#') {
										//If review done, start the remove process
										if (value.li_attr.review == "Done") {
											//Get the current node
											var node = $('#containerTree').jstree(true).get_node(value.id);
											//Get parent ID
											var parentID = $('#containerTree').jstree(true).get_parent(node);
											//Get Parent Node
											var nodeParent = $('#containerTree').jstree(true).get_node(parentID);
											//Delete visit Node
											$('#containerTree').jstree(true).delete_node(node);
											//Check if parent now empty, if yes, remove the parent (patient node)
											if (nodeParent.children.length == 0) {
												$('#containerTree').jstree(true).delete_node(nodeParent);
											}

										}
									}
								});
							}
						}
					};
				}
			}*/
		});

		$('#containerTree').on('select_node.jstree', function(e, data) {
			var selectedNode = data.node;
			//if Patient node (parent is root tree), load the study interface
			if (data.instance.get_parent(selectedNode) == '#') {
				var selectedPatientNumber = selectedNode.id;
				$("#contenu").load('/patient_interface', {
					patient_num: selectedPatientNumber
				});

				//Open the selected node
				$('#containerTree').jstree(true).open_node(selectedPatientNumber);

			} else {
				var selectedPatientNumber = data.instance.get_node(selectedNode.parent).id;
				var selectedIdVisit = data.instance.get_node(selectedNode).id;
				//Load visit interface
				$("#contenu").load('/visit_interface', {
					id_visit: selectedIdVisit,
					type_visit: selectedNode.text,
					patient_num: selectedPatientNumber
				});

			}
		})

		//Search function in the JSTree
		$(function() {
			var to = false;
			$('#search').keyup(function() {
				if (to) {
					clearTimeout(to);
				}
				to = setTimeout(function() {
					var v = $('#search').val();
					$('#containerTree').jstree(true).search(v);
				}, 250);
			});
		});

		$("#investigatorDialog").dialog({
			autoOpen: false,
			width: 'auto',
			height: 'auto'
		});

	});
</script>

<div>
	<div class="row">
		<div class="col text-right">
			<button id="documentationButton" class="btn btn-dark">Documentation</button>
			<?php
			if ($_SESSION['role'] == User::INVESTIGATOR) {
				?>
			<button id="uploadApp" class="btn btn-dark">Multi Uploader</button>
			<?php
			}
			?>
		</div>
	</div>
	<div id="tree" class="row">
		<aside id="tree-aside" class="col-3">
			<input class="form-control taille" id="search" type="text" placeholder="Search...">
			<div id="containerTree"></div>
		</aside>
		<div id="contenu" class="col-9"></div>
	</div>
</div>
<div id="investigatorDialog"></div>