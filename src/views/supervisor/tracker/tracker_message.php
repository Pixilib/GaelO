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

<div id="trackerDiv" class="trackerRoleDiv">
	<h1>Messages Logs</h1>
    <table id="trackerTableSupervisor" class="table table-striped" style="text-align:center; width:100%">
        <thead>
            <tr>
            <th>Date</th>
            <th>Username</th>
            <th>Role</th>
            <th>Message</th>
            </tr>
            <tr>
            <th><input type="text" placeholder="Search" class="column_search" style="max-width:75px" /></th>
            <th><input type="text" placeholder="Search" class="column_search" style="max-width:75px" /></th>
            <th><input type="text" placeholder="Search" class="column_search" style="max-width:75px" /></th>
            <th><input type="text" placeholder="Search" class="column_search" style="max-width:75px"/> </th>
            </tr>
        </thead>
        <tbody>
        <?php 
		foreach ($trackerMessages as $messageEvent) {
			$eventDetails=json_decode($messageEvent['action_details'], true);
		?>
        	<tr>
				<td><?=$messageEvent['date']?></td>
				<td><?=htmlspecialchars($messageEvent['username'])?></td>
				<td><?=$messageEvent['role']?></td>
				<td>
					<?php 
					echo('<pre><code>'.json_encode($eventDetails, JSON_PRETTY_PRINT|JSON_HEX_QUOT|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS).'</code></pre>');
					?>
				</td>
			</tr>
		<?php 
		}
		?>
        </tbody>
    </table>
</div>