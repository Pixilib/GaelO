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
 * Display patient's information for supervisor with Withdraw button to set patient as withdrawn
 */

Session::checkSession();
$linkpdo=Session::getLinkpdo();

$userObject=new User($_SESSION['username'], $linkpdo);
$accessCheck=$userObject->isPatientAllowed($_POST['patient_num'], $_SESSION['role']);

if ($accessCheck && $_SESSION['role'] == User::SUPERVISOR ) {
    
	$patientObject = new Patient($_POST['patient_num'], $linkpdo);
    
	if(isset($_POST['initials'])){
        
		try{
			$patientObject->editPatientDetails($_POST['initials'], $_POST['gender'], $_POST['birthdate'], 
				$_POST['registrationDate'], $_POST['investigator'], $_POST['center']);
			$answer=true;
            
			$editDetails['patient_code']=$_POST['patient_num'];
			$editDetails['initials']=$_POST['initials'];
			$editDetails['gender']=$_POST['gender'];
			$editDetails['birthdate']=$_POST['birthdate'];
			$editDetails['registrationDate']=$_POST['registrationDate'];
			$editDetails['investigator']=$_POST['investigator'];
			$editDetails['center']=$_POST['center'];
            
			Tracker::logActivity($_SESSION['username'], $_SESSION['role'], $patientObject->patientStudy, null , "Edit Patient", $editDetails);
            
		}catch(Exception $e){
			error_log($e->getMessage());
			$answer=false;
		}
        
		echo(json_encode($answer));
        
	}else{
		$visitsObjects=$patientObject->getAllCreatedPatientsVisits(false);
		$visitsObjectDeleted=$patientObject->getAllCreatedPatientsVisits(true);
		foreach ($visitsObjectDeleted as $visitDeleted){
			$visitsObjects[]=$visitDeleted;
		} 
        
		require 'views/supervisor/patient_infos_view.php';
	}
    
}else {
	require 'includes/no_access.php';
}
