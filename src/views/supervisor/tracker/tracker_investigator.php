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
    	<h1>Investigator's Logs</h1>
    	<div class="control-group">
    		<div class="controls span2">
        		<input type="checkbox" class="columnSelect" id="Create Visit" value=2 checked>
        		<label for="Create Visit">Create Visit</label>
            	<input type="checkbox" class="columnSelect" id="Upload Series" value=3 checked>
            	<label for="Upload Series">Upload Series</label>
            	<input type="checkbox" class="columnSelect" id="Investigator Form" value=4 checked>
            	<label for="Investigator Form">Investigator Form</label>
        	</div>
        	<div class="controls span2">
            	<input type="checkbox" class="columnSelect" id="Corrective Action" value=5 checked>
            	<label for="Corrective Action">Corrective Action</label>
            	<input type="checkbox" class="columnSelect" id="Change Serie" value=6 checked>
            	<label for="Change Serie">Change Serie</label>
            	<input type="checkbox" class="columnSelect" id="Delete Visit" value=7 checked>
            	<label for="Delete Visit">Delete Visit</label>
        	</div>
		</div>
        <table id="trackerTableSupervisor" class="table table-striped" style="text-align:center; width:100%">
            <thead>
                <tr>
                <th>Date</th>
                <th>Username</th>
                <th>Create Visit</th>
                <th>Upload Series</th>
                <th>Investigator Form</th>
                <th>Corrective Action</th>
                <th>Change Serie</th>
                <th>Delete Visit</th>
                </tr>
                <tr>
                <th><input type="text" placeholder="Search" class="column_search" style="max-width:75px" /></th>
                <th><input type="text" placeholder="Search" class="column_search" style="max-width:75px" /></th>
                <th><input type="text" placeholder="Search" class="column_search" style="max-width:75px" /></th>
                <th><input type="text" placeholder="Search" class="column_search" style="max-width:75px"/> </th>
                <th><input type="text" placeholder="Search" class="column_search" style="max-width:75px"/> </th>
                <th><input type="text" placeholder="Search" class="column_search" style="max-width:75px"/> </th>
                <th><input type="text" placeholder="Search" class="column_search" style="max-width:75px"/> </th>
                <th><input type="text" placeholder="Search" class="column_search" style="max-width:75px"/> </th>
                </tr>
            
            </thead>
            <tbody>
            <?php 
			foreach ($trackerData as $investigatorEvent) {
				$eventDetails=json_decode($investigatorEvent['action_details'], true);
			?>
            	<tr>
					<td><?=$investigatorEvent['date']?></td>
					<td><?=htmlspecialchars($investigatorEvent['username'])?></td>
					<td><?php if ($investigatorEvent['action_type'] == "Create Visit") echo('<pre><code>'.json_encode($eventDetails, JSON_PRETTY_PRINT|JSON_HEX_QUOT|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS).'</code></pre>')?></td>
					<td><?php if ($investigatorEvent['action_type'] == "Upload Series") echo('<pre><code>'.json_encode($eventDetails, JSON_PRETTY_PRINT|JSON_HEX_QUOT|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS).'</code></pre>')?></td>
					<td><?php if ($investigatorEvent['action_type'] == "Save Form") {
						$specificForm=$eventDetails['raw_data'];
						unset($eventDetails['raw_data']);
						echo('<pre><code>'.json_encode($eventDetails, JSON_PRETTY_PRINT|JSON_HEX_QUOT|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS).'</code></pre>');
						?>
						
						<a tabindex="0" role="button" 
							data-trigger="focus" 
							class="btn btn-primary popover-dismiss" 
							data-container="body" 
							data-html="true"
							title="form details"
							data-toggle="popover" 
							data-placement="right" 
							data-content="<?=htmlspecialchars('<pre><code>'.json_encode($specificForm, JSON_PRETTY_PRINT|JSON_HEX_QUOT|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS).'</code></pre>')?>">
						Show Form
						</a>
						<?php 
						}
						?>
					</td>
					<td><?php if ($investigatorEvent['action_type'] == "Corrective Action") echo('<pre><code>'.json_encode($eventDetails, JSON_PRETTY_PRINT|JSON_HEX_QUOT|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS).'</code></pre>')?></td>
					<td><?php if ($investigatorEvent['action_type'] == "Change Serie") echo('<pre><code>'.json_encode($eventDetails, JSON_PRETTY_PRINT|JSON_HEX_QUOT|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS).'</code></pre>')?></td>
					<td><?php if ($investigatorEvent['action_type'] == "Delete Visit") echo('<pre><code>'.json_encode($eventDetails, JSON_PRETTY_PRINT|JSON_HEX_QUOT|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS).'</code></pre>')?></td>
				</tr>
			<?php 
			}
			?>
			</tbody>
        </table>
</div>