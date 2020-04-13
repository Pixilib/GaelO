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
    $('#userRoles').DataTable( {
    	"sDom": 'lrtip',
    	"bSortCellsTop": true,
    	"scrollX": true
    });

	// Search function on datatable
	$('#userDetailsRoles').on('keyup', ".column_search_user_role", function() {
		$('#userRoles').DataTable()
			.column($(this).parent().index())
			.search(this.value)
			.draw();
	});
	
</script>

<div id="userDetailsRoles" style="overflow-x: auto">
	<h1>Users Details</h1>
	<table class="table table-striped" id="userRoles"
		style="text-align: center; width: 100%">
		<thead>
			<tr>
				<th>Username</th>
				<th>Roles</th>
			</tr>
			<tr>
				<th><input type="text" placeholder="Search"
					class="column_search_user_role" style="max-width: 75px" /></th>
				<th><input type="text" placeholder="Search"
					class="column_search_user_role" style="max-width: 75px" /></th>
			</tr>
		</thead>
		<tbody>
			<?php
			foreach ($rolesList as $username => $role) {
				?>
                <tr>
                <th><?=htmlspecialchars($username)?></th>
                <th><?=implode(', ', $role)?></th>
                </tr>
                <?php 
			}
			?>
		</tbody>
	</table>
</div>