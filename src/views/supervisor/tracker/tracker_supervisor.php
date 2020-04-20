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
	<h1>Supervisor's Logs</h1>
	<div class="control-group">
		<div class="controls span2">
        	<input type="checkbox" class="columnSelect" id="Import Patient" value=3 checked>
        	<label for="Import Patient">Import Patient</label>
        	<input type="checkbox" class="columnSelect" id="Edit Patient" value=4 checked>
        	<label for="Edit Patient">Edit Patient</label>
        	<input type="checkbox" class="columnSelect" id="Patient Withdraw" value=5 checked>
        	<label for="Patient Withdraw">Patient Withdraw</label>
        	<input type="checkbox" class="columnSelect" id="Delete Visit" value=6 checked>
        	<label for="Delete Visit">Delete Visit</label>
        	<input type="checkbox" class="columnSelect" id="Change Serie" value=7 checked>
        	<label for="Change Serie">Change Serie</label>
        	<input type="checkbox" class="columnSelect" id="Reset QC" value=8 checked>
        	<label for="Reset QC">Reset QC</label>
    	</div>
    	<div class="controls span2">
        	<input type="checkbox" class="columnSelect" id="Unlock Form" value=9 checked>
        	<label for="Unlock Form">Unlock Form</label>
        	<input type="checkbox" class="columnSelect" id="Delete Form" value=10 checked>
        	<label for="Delete Form">Delete Form</label>
        	<input type="checkbox" class="columnSelect" id="Add Documentation" value=11 checked>
        	<label for="Add Documentation">Add Documentation</label>
        	<input type="checkbox" class="columnSelect" id="Update Documentation" value=12 checked>
        	<label for="Update Documentation">Update Documentation</label>
        	<input type="checkbox" class="columnSelect" id="Reactivate Visit" value=13 checked>
        	<label for="Reactivate Visit">Reactivate Visit</label>
    	</div>
	</div>
	
    <table id="trackerTableSupervisor" class="table table-striped" style="text-align:center; width:100%">
        <thead>
            <tr>
            <th>Date</th>
            <th>Username</th>
            <th>Patient / Visit</th>
            <th>Import Patients</th>
            <th>Edit Patients</th>
            <th>Patient withdraw</th>
            <th>Delete Visit</th>
            <th>Change Serie</th>
            <th>Reset QC</th>
            <th>Unlock Form</th>
            <th>Delete Form</th>
            <th>Add Documentation</th>
            <th>Update Documentation</th>
            <th>Reactivate Visit</th>
            
            </tr>
            <tr>
            <th><input type="text" placeholder="Search" class="column_search" style="max-width:75px" /></th>
            <th><input type="text" placeholder="Search" class="column_search" style="max-width:75px" /></th>
            <th><input type="text" placeholder="Search" class="column_search" style="max-width:75px" /></th>
            <th><input type="text" placeholder="Search" class="column_search" style="max-width:75px"/> </th>
            <th><input type="text" placeholder="Search" class="column_search" style="max-width:75px" /></th>
            <th><input type="text" placeholder="Search" class="column_search" style="max-width:75px" /></th>
            <th><input type="text" placeholder="Search" class="column_search" style="max-width:75px" /></th>
            <th><input type="text" placeholder="Search" class="column_search" style="max-width:75px" /></th>
            <th><input type="text" placeholder="Search" class="column_search" style="max-width:75px" /></th>
            <th><input type="text" placeholder="Search" class="column_search" style="max-width:75px" /></th>
            <th><input type="text" placeholder="Search" class="column_search" style="max-width:75px" /></th>
            <th><input type="text" placeholder="Search" class="column_search" style="max-width:75px" /></th>
            <th><input type="text" placeholder="Search" class="column_search" style="max-width:75px" /></th>
            <th><input type="text" placeholder="Search" class="column_search" style="max-width:75px" /></th>
            </tr>
        
        </thead>
        <tbody>
        <?php
		foreach ($trackerData as $supervisorEvent) {
			$eventDetails=json_decode($supervisorEvent['action_details'], true);
		?>
        	<tr>
				<td><?=$supervisorEvent['date']; ?></td>
				<td><?=htmlspecialchars($supervisorEvent['username'])?></td>
				<td><?php 
				$string="";
				if (isset($eventDetails['patient_code'])) {
					$string=$string.$eventDetails['patient_code']." / ";
					unset($eventDetails['patient_code']);
				}
				if (isset($eventDetails['type_visit'])) {
					$string=$string.$eventDetails['type_visit'];
					unset($eventDetails['type_visit']);
				}
				echo($string);
					?>
				</td>
				<td>
					<?php 
					if ($supervisorEvent['action_type'] == "Import Patients") {
						?>			
							<a tabindex="0" role="button" 
								data-trigger="focus" 
								class="btn btn-primary popover-dismiss" 
								data-container="body" 
								data-html="true"
								title="form details"
								data-toggle="popover" 
								data-placement="right" 
								data-content="<?=htmlspecialchars('<pre><code>'.json_encode($eventDetails, JSON_PRETTY_PRINT|JSON_HEX_QUOT|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS).'</code></pre>')?>">
							Show Details
							</a>
						<?php
					}
					?>
				</td>
				<td>
					<?php 
					if ($supervisorEvent['action_type'] == "Edit Patient") echo('<pre><code>'.json_encode($eventDetails, JSON_PRETTY_PRINT|JSON_HEX_QUOT|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS).'</code></pre>');
					?>
				</td>
				<td>
					<?php 
					if ($supervisorEvent['action_type'] == "Patient Withdraw") echo('<pre><code>'.json_encode($eventDetails, JSON_PRETTY_PRINT|JSON_HEX_QUOT|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS).'</code></pre>');
					?>
				</td>
				<td>
					<?php 
					if ($supervisorEvent['action_type'] == "Delete Visit") echo('<pre><code>'.json_encode($eventDetails, JSON_PRETTY_PRINT|JSON_HEX_QUOT|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS).'</code></pre>');
					?>
				</td>
				<td>
					<?php 
					if ($supervisorEvent['action_type'] == "Change Serie") echo('<pre><code>'.json_encode($eventDetails, JSON_PRETTY_PRINT|JSON_HEX_QUOT|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS).'</code></pre>');
					?>
				</td>	
				<td> 
					<?php 
					if ($supervisorEvent['action_type'] == "Reset QC") echo('<pre><code>'.json_encode($eventDetails, JSON_PRETTY_PRINT|JSON_HEX_QUOT|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS).'</code></pre>');
					?>
				</td>
				<td> 
					<?php 
					if ($supervisorEvent['action_type'] == "Unlock Form") echo('<pre><code>'.json_encode($eventDetails, JSON_PRETTY_PRINT|JSON_HEX_QUOT|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS).'</code></pre>');
					?>
				</td>
				<td> 
					<?php 
					if ($supervisorEvent['action_type'] == "Delete Form") echo('<pre><code>'.json_encode($eventDetails, JSON_PRETTY_PRINT|JSON_HEX_QUOT|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS).'</code></pre>');
					?>
				</td>
				<td> 
					<?php 
					if ($supervisorEvent['action_type'] == "Add Documentation") echo('<pre><code>'.json_encode($eventDetails, JSON_PRETTY_PRINT|JSON_HEX_QUOT|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS).'</code></pre>');
					?>
				</td>
				<td> 
					<?php 
					if ($supervisorEvent['action_type'] == "Update Documentation") echo('<pre><code>'.json_encode($eventDetails, JSON_PRETTY_PRINT|JSON_HEX_QUOT|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS).'</code></pre>');
					?>
				</td>
				<td>
					<?php 
					if ($supervisorEvent['action_type'] == "Reactivate Visit") echo('<pre><code>'.json_encode($eventDetails, JSON_PRETTY_PRINT|JSON_HEX_QUOT|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS).'</code></pre>');
					?>
				</td>

			</tr>
		<?php 
		}
		?>
        </tbody>
    </table>
</div>