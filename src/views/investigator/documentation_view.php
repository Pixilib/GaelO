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
 
 <div>
	<table class="table table-borderless table-striped">
		<thead class="thead-light">
			<tr>
				<th scope="col"></th>
				<th scope="col">Name of the document</th>
				<th scope="col">Version</th>
				<th scope="col">Application date</th>
			</tr>
		</thead>
		<tbody>
          <?php
		// Fill the table for each database response
		foreach ($documentationObjects as $documentation) {
			?>
           	<tr><td><img class="icon" src="assets/images/download.png" alt="Download" onclick="downloadDocumentation(<?=$documentation->documentId?>)"></td>
            <td><?= htmlspecialchars($documentation->documentName)?></td>
            <td><?= htmlspecialchars($documentation->documentVersion)?></td>
            <td><?= $documentation->documentDate?></td></tr>
        <?php
		}
		?>
        </tbody>
	</table>
</div>