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

/**
 * Delete a visit and relative images in Orthanc
 */
require_once($_SERVER['DOCUMENT_ROOT'].'/vendor/autoload.php');

Session::checkSession();
$linkpdo=Session::getLinkpdo();

$id_visit=$_POST['id_visit'];
$reason=$_POST['reason'];

if (isset($_SESSION['username'])) {

	$username=$_SESSION['username'];
	$role=$_SESSION['role'];

	//Get visit details
	$visitObject=new Visit($id_visit, $linkpdo);
  
	//Get stateQC status and study name for acess control
	$qualityControlStatus=$visitObject->stateQualityControl;
	$study=$visitObject->study;

	$userObject=new User($username, $linkpdo);
	$permissionStudy=$userObject->isRoleAllowed($study, User::INVESTIGATOR);
	$isSupervisor=$userObject->isRoleAllowed($study, User::SUPERVISOR);
   
	//if investigator and quality control neither accpted or refused,  or supervisor Role => Allow delete of visit
	if( ($role==User::INVESTIGATOR && $permissionStudy && $qualityControlStatus !=Visit::QC_ACCEPTED && $qualityControlStatus !=Visit::QC_REFUSED) || ($isSupervisor && $role==User::SUPERVISOR ) ){
        
		//Delete the Visit by changing the boolean deleted value in table
		$visitObject->changeDeletionStatus(true);
        
		//Log Delete operation
		$actionDetails["patient_code"]=$visitObject->patientCode;
		$actionDetails["type_visit"]=$visitObject->visitType;
		$actionDetails['modality_visit']=$visitObject->visitGroupObject->groupModality;
		$actionDetails["visit"]="Deleted";
		$actionDetails["reason"]=$reason;
		Tracker::logActivity($username, $role, $study, $id_visit, "Delete Visit", $actionDetails);
		$answer=true;
	} else {
		$answer=false;
	}
	
	echo(json_encode($answer));

}else {
	echo(json_encode(false));
}
