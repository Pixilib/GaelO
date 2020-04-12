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
	
	$(document).ready(function () {

		$('#tableau_admin').DataTable({
				"sDom": 'lrtip',
				"bSortCellsTop": true,
				"scrollX": true
			}
		);
		
		$( '#adminUserDiv'  ).on( 'keyup', ".column_search" ,function () {
			$('#tableau_admin').DataTable()
				.column( $(this).parent().index() )
				.search( this.value )
				.draw();
		} );

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
				<th><input type="text" placeholder="Search" class="column_search" style="max-width:75px" /></th>
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

		<a href="scripts/export_users.php" class="btn btn-primary">Export Users Details</a>

	</div>
	
</div>	