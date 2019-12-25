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
 * Visit related API
 * Get : get Visit data by ID
 * Post : Create Visit
 * PUT : Delete or Reactivate Visit
 */

require_once($_SERVER['DOCUMENT_ROOT'].'/vendor/autoload.php');

Session::checkSession();
$linkpdo = Session::getLinkpdo();

$study = $_SESSION['study'];
$username = $_SESSION['username'];

if($_SERVER['REQUEST_METHOD']==='GET'){

}else if ($_SERVER['REQUEST_METHOD']==='POST'){

    $patientCode = $_POST['patient_num'];
    $userObject= new User($username, $linkpdo);
    $patientAllowed = $userObject->isPatientAllowed($patientCode, $_SESSION['role']);

    // Check user allowance (only available for an investigator)
    if (isset($_SESSION['username']) && $_SESSION['role'] == User::INVESTIGATOR && $patientAllowed) {
            
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
            
            if($statusDone === 'Not Done'){

                $emailObject = new Send_Email($linkpdo);

                $message="A Not Done visit has been created <br>
                Patient Number : ".$patientCode."<br>
                Study : ".$study."<br> 
                Visit Type : ".$visitType."<br>
                Creating Username : ".$userObject->lastName." ".$userObject->firstName."<br>";

                $supervisorsEmails=$emailObject->getRolesEmails(User::SUPERVISOR, $study);
                $emailObject->setMessage($message);
                $emailObject->sendEmail($supervisorsEmails, "Visit Not Done");

            }

            echo(json_encode($answer));
    }   

}else if ($_SERVER['REQUEST_METHOD']==='PUT'){

}