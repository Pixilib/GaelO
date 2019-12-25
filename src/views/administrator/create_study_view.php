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


 
    //SK A FAIRE
    //CHECK UNICITE STUDY
    //CHECK UNICITE VISITE NAME
	//CHECK UNICITE VISIT ORDER
	//REDUIRE NOTRE COLONNES DES NOMBRES
	//TESTER CREATION DE VISITE
	
?>

<script type="text/javascript">
	$(document).ready(function() {

		$("#newVisitBtn").on('click', function(){

			let row="<tr> \
						<td contenteditable=true>Name</td> \
						<td contenteditable=true><input type=\"number\" min=0 class=\"\"/></td> \
						<td><input type=\"checkbox\" class=\"\"/ checked ></td> \
						<td><input type=\"checkbox\" class=\"\"/ checked></td> \
						<td><input type=\"checkbox\" class=\"\"/ checked></td> \
						<td><input type=\"checkbox\" class=\"\"/ checked></td> \
						<td contenteditable=true><input type=\"number\" min=0 class=\"\"/></td> \
						<td contenteditable=true><input type=\"number\" min=0 class=\"\"/></td> \
						<td>\
							<select class=\"\">\
								<option value=\"Default\">Default</option> \
								<option value=\"Full\">Full</option> \
							</select> \
						</td> \
						<td><input type=\"button\" value=\"Remove\" class=\"btn btn-danger\" onClick=\"removeRow(this)\"/></td> \
					</tr>"

			$("#visitTable").append(row)

		})

		$('#createStudyBtn').on('click', function() {

			let studyName = $("#studyName").val();

			let dataArray={
				studyName : studyName,
				visits : []
			}

			$('#visitTable > tbody  > tr').each(function(index, tr) {

				let visitName=$(this).find("td:eq(0)").text();
				let visitOrder=$(this).find("td:eq(1) > input[type='number']").val();
				let localForm=$(this).find("td:eq(2) > input[type='checkbox']").is(':checked');
				let qc=$(this).find("td:eq(3) > input[type='checkbox']").is(':checked');
				let review=$(this).find("td:eq(4) > input[type='checkbox']").is(':checked');
				let optional=$(this).find("td:eq(5) > input[type='checkbox']").is(':checked');
				let dayMin=$(this).find("td:eq(6)  > input[type='number']").val();
				let dayMax=$(this).find("td:eq(7)  > input[type='number']").val();
				let anonProfile=$(this).find("td:eq(8)  > select").find(":selected").val();

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
				dataArray.visits.push(visitObject)
			})

			$.ajax({
				type: "POST",
        		url: 'scripts/create_study.php',
        		dataType: 'json',
				data  : dataArray,
				success : function(data){
					console.log(data)
				}

			})
			
		})

	});

	function removeRow(row){
		$(row).closest('tr').remove();
	}

</script>
<div class="jumbotron">
		<div class="form-group row">
			<label class="col-form-label">Study Name:</label> <input type="text" class="form-control" id="studyName" name="studyName" placeholder="study Name" maxlength="32" required>
		</div>
			
		<div>
			<label class="col-form-label">Visits In Study :</label>
			<table id="visitTable" class="table table-striped">
				<thead>
					<tr>
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
					</tr>
				</thead>
				<tbody>

				</tbody>
			</table>
			<input type="button" id ="newVisitBtn" class="btn btn-primary" value = "New Visit"/>
		</div>

		<button type="button" id="createStudyBtn" class="btn btn-primary">Create
			Study</button>

		<button type="button" id="createSubmit" class="btn btn-primary">Create
			Study</button>

</div>