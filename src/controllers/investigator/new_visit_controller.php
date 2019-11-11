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
 * Form to create a new visit (as defined in the table) for the current study (and form processing)
 */

Session::checkSession();
$linkpdo = Session::getLinkpdo();

$study = $_SESSION['study'];
$username = $_SESSION['username'];
$patientCode = $_POST['patient_num'];

$userObject= new User($username, $linkpdo);
$patientAllowed = $userObject->isPatientAllowed($patientCode, $_SESSION['role']);

// Check user allowance (only available for an investigator)
if (isset($_SESSION['username']) && $_SESSION['role'] == User::INVESTIGATOR && $patientAllowed) {
    
    // If form validated, processing data
    if (isset($_POST['validate'])) {
        
        $visitType = $_POST['visite'];
        $statusDone = $_POST['done_not_done'];
        $reasonNotDone = $_POST['reason'];
        $acquisitionDate = $_POST['acquisition_date'];
        if (empty($acquisitionDate)) {
            $acquisitionDate = null;
        }
        
        if (! empty($visitType) && ! empty($statusDone)) {
            
            $createdId = Visit::createVisit($visitType, $_POST['study'], $patientCode, $statusDone, $reasonNotDone, $acquisitionDate, $username, $linkpdo);
            // Log action
            $actionDetails['patient_code'] = $patientCode;
            $actionDetails['type_visit'] = $visitType;
            Tracker::logActivity($username, $_SESSION['role'], $study, $createdId, "Create Visit", $actionDetails);
            $answer="Success";
        }else{
            $answer="Missing Data";
        }
        
        echo(json_encode($answer));
        
    } else {
        // Get available Visit for creation from the study manager object
        // Usually only one visit to create to respect visit order but could be multipled
        // if an intermediate study has been deleted
        $visitManager = new Visit_Manager($patientCode, $linkpdo);
        $typeVisiteDispo = $visitManager->getNextVisitToCreate();
        
        require 'views/investigator/new_visit_view.php';
    }
} else {
    require 'includes/no_access.php';
}
