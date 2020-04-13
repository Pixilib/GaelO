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

/**
 * Build patient table with patient details
 * @param Patient $patient
 */
function build_patient_visit_table(Patient $patient) {
	if ($patient->patientWithdraw)
		$patientStatus="Withdrawn";
	else
		$patientStatus="Included";
    
	$patientCenter=$patient->getPatientCenter();
	?>
	<div style="overflow-x:auto;"> 
    	<table id='tab_patient' class='table table-striped table-sm table-border'>
        	<tr>
        		<td colspan=2><b>Patient information</b></td>
        	</tr>
        	<tr>
        		<td>Patient number</td>
        		<td><?=$patient->patientCode?></td>
        	</tr>
        	<tr>
        		<td>Initials</td>
        		<td><?=$patient->patientLastName.$patient->patientFirstName?></td>
        	</tr>
        	<tr>
        		<td>Status</td>
        		<td><?=$patientStatus?></td>
        	</tr>
        	<tr>
        		<td>Gender</td>
        		<td><?=$patient->patientGender?></td>
        	</tr>
        	<tr>
        		<td>Birthday</td>
        		<td><?=$patient->patientBirthDate?></td>
        	</tr>
        	<tr>
        		<td>Registration date</td>
        		<td><?=$patient->patientRegistrationDate?></td>
        	</tr>
        	<tr>
        		<td>Number investigator center</td>
        		<td><?= $patientCenter->code.' - '.htmlspecialchars($patientCenter->name) ?></td>
        	</tr>
        </table>
    </div>
<?php 
}

/**
 * Build table listing visits with status of a patient
 * @param array $visitObjects
 * @param string $role
 */
function build_visit_details_table(array $visitObjects, string $role) { ?>
    <div style="overflow-x:auto;">
		<table id='tab_visits' class='table table-striped table-border'>
			<tr>
				<th colspan=7>Visit information</th>
			</tr>
			<tr>
				<td rowspan=2>Modality</td>	
				<td rowspan=2>Visit</td>
    	        <td colspan=4>Visit status</td>
    	        <td rowspan=2>Number of series</td>
    	        <td rowspan=2>Acquisition date</td>
			</tr>
			<tr>
    	        <td>Visit</td>
    	        <td>Series upload</td>
    	        <td>Investigator form</td>
    	        <td>Quality control</td>
			</tr>
        	<?php 
			foreach ($visitObjects as $visitObject) {
				$numberOfSeries=count($visitObject->getSeriesOrthancID());
                
				if ($numberOfSeries == 0) $numberOfSeries="Not Uploaded";
				?>    
                <tr>
					 <td><?=$visitObject->visitGroupObject->groupModality?></td>
					 <td class="visitLink" data-visitid=<?=$visitObject->id_visit?> ><?=htmlspecialchars($visitObject->visitType)?></td>
        	         <td><?=$visitObject->statusDone?></td>
        	         <td><?=$visitObject->uploadStatus?></td>
        	         <td><?=$visitObject->stateInvestigatorForm?></td>
        	         <td><?=$visitObject->stateQualityControl?></td>
        	         <td><?=$numberOfSeries?></td>
        	         <td><?=$visitObject->acquisitionDate?></td>
                <?php 
				if ($role == User::INVESTIGATOR && (in_array($visitObject->stateQualityControl, array(Visit::QC_NOT_DONE, Visit::QC_CORRECTIVE_ACTION_ASKED)))) {
					?>
        			<td>
        				<a href=scripts/delete_visit.php?id_visit=<?=$visitObject->id_visit?> class="ajaxLinkConfirm">
        					<img class="icon" src="assets/images/corbeille.png" alt="Delete">
        				</a>
        			</td> 
        	        <?php
				}
				?>
                </tr>
            <?php
			}
			?>
    </table> 
    </div>
<?php     
}
?>