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
		
		async function getExistingStudies(){

			let existingStudies = await fetch('scripts/study.php').then((answer)=>{
				return answer.json()
			})
			
			return existingStudies;
		}

		$("#newVisitBtn").on('click', function(){

			let row="<tr> \
						<td>\
							<select class=\"\">\
								<option value=\"PT\">PT</option> \
								<option value=\"CT\">CT</option> \
								<option value=\"MR\">MR</option> \
								<option value=\"RTSTRUCT\">RTSTRUCT</option> \
								<option value=\"OP\">OP</option> \
							</select> \
						</td> \
						<td contenteditable=true>Name</td> \
						<td contenteditable=true><input type=\"number\" min=0 max=100 value=0 class=\"\"/></td> \
						<td><input type=\"checkbox\" class=\"\"/ checked ></td> \
						<td><input type=\"checkbox\" class=\"\"/ checked></td> \
						<td><input type=\"checkbox\" class=\"\"/ checked></td> \
						<td><input type=\"checkbox\" class=\"\"/ ></td> \
						<td contenteditable=true><input type=\"number\" min=-10000 max=10000 value=0 class=\"\"/></td> \
						<td contenteditable=true><input type=\"number\" min=-10000 max=10000 value=0 class=\"\"/></td> \
						<td>\
							<select class=\"\">\
								<option value=\"Default\">Default</option> \
								<option value=\"Full\">Full</option> \
							</select> \
						</td> \
						<td contenteditable=true> "+ JSON.stringify(
							{ "seriesDescriptionConstraints" : [],
							"structureSetConstraints": { "optional" : [], "mandatory" : []} }

						)+"</td> \
						<td><input type=\"button\" value=\"Remove\" class=\"btn btn-danger\" onClick=\"removeRow(this)\"/></td> \
					</tr>"

			$("#visitTable").append(row)

		})

		$('#createStudyBtn').on('click', async function() {

			let studyNameString = $("#studyName").val();
			let patientCodePrefix = $("#patientCodePrefix").val();

			let dataArray={};

			$('#visitTable > tbody  > tr').each(function(index, tr) {
				let visitModality=$(this).find("td:eq(0)  > select").find(":selected").val();
				let visitName=$(this).find("td:eq(1)").text();
				let visitOrder=$(this).find("td:eq(2) > input[type='number']").val();
				let localForm=$(this).find("td:eq(3) > input[type='checkbox']").is(':checked');
				let qc=$(this).find("td:eq(4) > input[type='checkbox']").is(':checked');
				let review=$(this).find("td:eq(5) > input[type='checkbox']").is(':checked');
				let optional=$(this).find("td:eq(6) > input[type='checkbox']").is(':checked');
				let dayMin=$(this).find("td:eq(7)  > input[type='number']").val();
				let dayMax=$(this).find("td:eq(8)  > input[type='number']").val();
				let anonProfile=$(this).find("td:eq(9)  > select").find(":selected").val();
				let dicomConstraints = undefined
				try{
					dicomConstraints=JSON.parse( $(this).find("td:eq(10)").text());
				} catch (error){ }
				

				let visitObject = {
					name : visitName,
					order : visitOrder,
					localForm :  localForm,
					qc : qc,
					review : review,
					optional : optional,
					dayMin : dayMin,
					dayMax : dayMax,
					anonProfile : anonProfile
				}

				if(dicomConstraints != null )visitObject['dicomConstraints'] = dicomConstraints
				
				//add visit in a modality property
				//if modality not found intialize and array to recieve visit objects
				if( "undefined" === typeof(dataArray[visitModality]) ){
					dataArray[visitModality]=[]
				}

				dataArray[visitModality].push(visitObject)
			})

			function checkDuplicateName(visitArray){
				let valueArr = visitArray.map(function(item){ return item.name });
				let isDuplicate = valueArr.some(function(item, idx){ 
					return valueArr.indexOf(item) != idx 
				});
				return isDuplicate;
			}

			function checkDuplicateOrder(visitArray){
				let valueArr = visitArray.map(function(item){ return item.order });
				let isDuplicate = valueArr.some(function(item, idx){ 
					return valueArr.indexOf(item) != idx 
				});
				return isDuplicate;
			}

			async function checkDuplicateStudy(){
				let existingStudies= await getExistingStudies();
				return existingStudies.includes(studyName);
			}

			let isDuplicateStudyName = await checkDuplicateStudy();

			let checkOrderNameContainsDuplicate=false;
			
			for (let modality in dataArray) {
				let isDuplicateName=checkDuplicateName(dataArray[modality])
				let isDuplicateOrder=checkDuplicateOrder(dataArray[modality])
				if(isDuplicateName || isDuplicateOrder) checkOrderNameContainsDuplicate=true;

			}

			if( checkOrderNameContainsDuplicate || isDuplicateStudyName ){
				alertifyError('Duplicate study / visit Name or Order by modality, should be unique')
				return;
			}
			console.log( JSON.stringify(dataArray));
			$.ajax({
					type: "POST",
					dataType: 'json',
					url: 'scripts/study.php',
					data: { studyName : studyNameString, patientCodePrefix : patientCodePrefix, visitsData : dataArray }, // serializes the form's elements.
					success: function(data) {
						if(data){
							alertifySuccess("Done")
							$("#adminDialog").dialog('close');
						}					
					}
			});	
		})

	});

	function removeRow(row){
		$(row).closest('tr').remove();
	}

</script>
<div class="jumbotron">
		<div class="form-group row">
			<label class="col-form-label">Study Name:</label> <input type="text" class="form-control" id="studyName" name="studyName" placeholder="study Name" maxlength="32" required>
			<label class="col-form-label">Patient Code Prefix :</label> <input type=number class="form-control" id="patientCodePrefix" name="patientCodePrefix" required>
		</div>
			
		<div>
			<label class="col-form-label">Visits In Study :</label>
			<table id="visitTable" class="table table-striped">
				<thead>
					<tr>
						<td>
							Visit Group
						</td>
						<td>
							Visit Name
						</td>
						<td>
							Visit Order
						</td>
						<td>
							Local Form Needed
						</td>
						<td>
							QC Needed
						</td>
						<td>
							Review Needed
						</td>
						<td>
							Optional Visit
						</td>
						<td>
							From Day From Inclusion
						</td>
						<td>
							To Day From Inclusion
						</td>
						<td>
							Anon Profile
						</td>
						<td>
							Dicom Constraints
						</td>
					</tr>
				</thead>
				<tbody>

				</tbody>
			</table>
			<input type="button" id ="newVisitBtn" class="btn btn-primary" value = "New Visit"/>
		</div>

		<button type="button" id="createStudyBtn" class="btn btn-primary">Create
			Study</button>

</div>