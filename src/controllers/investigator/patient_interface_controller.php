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
 * Display the patient interface (when click on patient node) with Add visit options if investigator
 */

Session::checkSession();
$linkpdo=Session::getLinkpdo();

$patient = $_POST['patient_num'];
$study = $_SESSION['study'];
$role = $_SESSION['role'];

$userObject=new User($_SESSION['username'], $linkpdo);
$patientAllowed=$userObject->isPatientAllowed($patient, $role);

//Check user authorization
if( isset($_SESSION['username']) && $patientAllowed ){
	
    //Instanciate Visit manager to check if visit creation is still possible
    $patientObject=new Patient($patient, $linkpdo);
    $visitPossible=$patientObject->getVisitManager()->isMissingVisit();
    
    $visitArray=$patientObject->getPatientsVisits();
    
    require 'includes/table_visit.php';
    require 'views/investigator/patient_interface_view.php';
    
    
} else {
    require 'includes/no_access.php';
}


