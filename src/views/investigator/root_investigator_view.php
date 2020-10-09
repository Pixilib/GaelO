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

				$("#tree").html('<div id="dicomUploaderv2" style="width:100%"></div>');

				window.Gaelo_Uploader.installUploader({
					developerMode: false,
					multiUpload: true,
					minNbOfInstances: 30,
					callbackOnStartAction: ()=>{
						preventAjaxDivLoading()
					},
					callbackOnUploadComplete: ()=>{
						//Remove prevent Ajax listener
						allowAjaxDivLoading()
						alertifySuccess("Multi Upload Finished")
						refreshInvestigatorDiv()
					}
				}, 'dicomUploaderv2')
				checkBrowserSupportDicomUpload('#dicomUploaderv2');

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
			"plugins": ["search", "contextmenu", "state"],
			"search": {
				"case_sensitive": false,
				"show_only_matches": true,
				"show_only_matches_children" : true
			},
			//Context menu for filtering actions
			"contextmenu": {
				"items": <?php 
				if ($_SESSION['role'] == User::INVESTIGATOR) {
					echo('investigatorContextMenu()');
				} else if ($_SESSION['role'] == User::CONTROLLER) {
					echo('controllerContextMenu()');
				} else if ($_SESSION['role'] == User::REVIEWER) {
					echo('reviewerContextMenu()');
				} else {
					echo('{}');
				} ?>

			}
		});

		function reviewerContextMenu(){

			return (
				{
					"Only Ongoing" : {
						"label" : "Only Ongoing",
						"action" : function (object) {
							filterVisitByClassName("Ongoing")
						}
					},
					"Only Adjucation" : {
						"label" : "Only Adjudication",
						"action" : function (object){
							filterVisitByClassName("WaitAdjudication")
						}

					}
				}
			)

		}

		function investigatorContextMenu(){

			return (
				{
					"Missing Images" : {
						"label" : "Missing Images",
						"action" : function (object) {
							filterVisitByClassName("NotUpload")
						}
					},
					"Missing Form" : {
						"label" : "Missing Form",
						"action" : function (object){
							filterVisitByClassName("NotForm")
						}
					},
					"Missing Both" : {
						"label" : "Missing Both",
						"action" : function (object){
							filterVisitByClassName("NotBoth")
						}
					}
				}
			)

		}

		function controllerContextMenu(){

			return (
				{
					"Awaiting QC" : {
						"label" : "Awaiting QC",
						"action" : function (object) {
							filterVisitByClassName("NotBoth")
						}
					}
				}
			)

		}

		function filterVisitByClassName(className) {
			let treeJson = $('#containerTree').jstree(true).get_json('#', {'flat': true})
			console.log(treeJson)
			let ongoingItems = treeJson.filter (function(item) {
				if( item.icon == "/assets/images/report-icon.png" && item.li_attr.class !== className){
					return true
				}

			}) 

			$('#containerTree').jstree(true).delete_node(ongoingItems)
			removeParentsIfNoChild()
		}

		/**
		 * Remove parents modality and patient with no childs
		 * SK : ALGO BOF BOF
		 */
		function removeParentsIfNoChild(){

			let treeJson = $('#containerTree').jstree(true).get_json('#', {'flat': true})

			let ongoingItems = treeJson.filter (function(item) {
				//Look at child only for modality level (has _ )
				if( item.id.includes("_") ) {

					let child = $('#containerTree').jstree(true).get_children_dom(item.id)
					//If no child remove the modality level
					if(child.length == 0){
						$('#containerTree').jstree(true).delete_node(item.id)
					}
					//Look if parrent still have other modalities
					let parentChildItems = $('#containerTree').jstree(true).get_children_dom(item.parent)
					if(parentChildItems.length == 0 ){
						$('#containerTree').jstree(true).delete_node(item.parent)
					} 

				}

			})

		}

		$('#containerTree').on('select_node.jstree', function(e, data) {
			let selectedNode = data.node;
			//if Patient node (parent is root tree), load the study interface
			if (selectedNode.parent == '#') {
				let selectedPatientNumber = selectedNode.id;
				$("#contenu").load('/patient_interface', {
					patient_num: selectedPatientNumber
				});

				//Open the selected node
				$('#containerTree').jstree(true).open_node(selectedPatientNumber);

			} else if ( selectedNode.children.length==0 ) {
				let selectedPatientNumber = selectedNode.parents[1];
				let selectedIdVisit = selectedNode.id;
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