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

<div id="trackerDiv" class="trackerRoleDiv">
	<h1>Controller's Logs</h1>
	
    <table id="trackerTableSupervisor" class="table table-striped" style="text-align:center; width:100%">
        <thead>
            <tr>
            <th>Date</th>
            <th>Username</th>
            <th>Patient</th>
            <th>Visit</th>
            <th>QC Decision</th>
            <th>Details</th>
            </tr>
            <tr>
            <th><input type="text" placeholder="Search" class="column_search" style="max-width:75px" /></th>
            <th><input type="text" placeholder="Search" class="column_search" style="max-width:75px" /></th>
            <th><input type="text" placeholder="Search" class="column_search" style="max-width:75px" /></th>
            <th><input type="text" placeholder="Search" class="column_search" style="max-width:75px" /></th>
            <th><input type="text" placeholder="Search" class="column_search" style="max-width:75px"/> </th>
            <th><input type="text" placeholder="Search" class="column_search" style="max-width:75px"/> </th>
            </tr>
        
        </thead>
        <tbody>
		<?php 
		foreach ($trackerData as $controllerEvent) {
	  		$eventDetails=json_decode($controllerEvent['action_details'], true);
		?>
        	<tr>
				<td><?=$controllerEvent['date']?></td>
				<td><?=$controllerEvent['username']?></td>
				<td><?=$eventDetails['patient_code']?> </td>
				<td><?=htmlspecialchars($eventDetails['type_visit'])?> </td>
				<td><?=@$eventDetails['qc_decision']?></td>
				<td> 
					<?php 
					unset($eventDetails['patient_code']);
					unset($eventDetails['type_visit']);
					unset($eventDetails['qc_decision']);
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