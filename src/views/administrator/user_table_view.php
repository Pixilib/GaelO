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
	
	$(document).ready(function () {

		$('#tableau_admin').DataTable({
				"sDom": 'lrtip',
				"bSortCellsTop": true,
				"scrollX": true
			}
		);

		//Search function in dataTable manual download
		$('#adminUserDiv').on('change keyup', ".column_search", function() {
			let searchValue = this.value
			let regex = false

			if($(this).prop("class").includes('select_search') && this.value != ""){
				searchValue = "^"+this.value+"$"
				regex = true
			}

			$('#tableau_admin').DataTable()
				.column( $(this).parent().index() )
				.search( this.value )
				.draw();
		});

	});

	function openModifyUser(username) {
		$( "#adminDialog" ).load('/modify_user&username='+username, function(){
			$("#adminDialog").dialog('option', 'title', 'Modify User');
			$( "#adminDialog" ).dialog('open');
		});
		
	}
	
</script>

<div>
	<div id="adminUserDiv">
		<table id="tableau_admin" class="table table-striped" style="width: 100%;">
		<thead>
			<tr>
				<th>username</th>
				<th>investigator center</th>
				<th>email</th>
				<th>last connexion</th>
				<th>account status</th>
				<th>Modify</th>
			</tr>
			<tr>
				<th><input type="text" placeholder="Search" class="column_search" style="max-width:75px" /></th>
				<th><input type="text" placeholder="Search" class="column_search" style="max-width:75px" /></th>
				<th><input type="text" placeholder="Search" class="column_search" style="max-width:75px" /></th>
				<th><input type="text" placeholder="Search" class="column_search" style="max-width:75px" /></th>
				<th>
					<select type="text" placeholder="Search" class="column_search select_search" style="max-width:75px" >
						<option value="">Choose</option>
						<option value="<?=User::ACTIVATED?>"><?=User::ACTIVATED?></option>
						<option value="<?=User::UNCONFIRMED?>"><?=User::UNCONFIRMED?></option>	
						<option value="<?=User::BLOCKED?>"><?=User::BLOCKED?></option>
						<option value="<?=User::DEACTIVATED?>"><?=User::DEACTIVATED?></option>
					</select>
				</th>
				<th></th>
			</tr>
		</thead>
		<tbody>
		<?php 
		///Fill the table with the data from the database
		foreach ($usersObjects as $user) {
		?>
			<tr>
				<td><?=htmlspecialchars($user->username)?></td>
				<td><?=$user->mainCenter?></td>
				<td><?=htmlspecialchars($user->userEmail)?></td>
				<td><?=$user->lastConnexionDate?></td>
				<td><?=$user->userStatus?></td>
				<td><input class="btn btn-secondary" type="button" value="Modify" onclick="openModifyUser('<?php echo($user->username)?>')"></td>
			</tr>
		<?php 
		}
		?>
		</tbody>
		</table>

	</div>
	
</div>	