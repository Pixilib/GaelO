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
 * Reset the Quality control of a visit ID
 */

header( 'content-type: text/html; charset=utf-8' );
require_once($_SERVER['DOCUMENT_ROOT'].'/vendor/autoload.php');

Session::checkSession();
$linkpdo=Session::getLinkpdo();

$id_visit = $_POST['id_visit'];
$reason = $_POST['reason'];

$userObject=new User($_SESSION['username'], $linkpdo);
$permissionCheck=$userObject->isVisitAllowed($id_visit, User::SUPERVISOR);

$visitObject=new Visit($id_visit, $linkpdo);

//If supervisor session and permission OK
if ($_SESSION['role']==User::SUPERVISOR && $permissionCheck && $visitObject->reviewStatus == Form_Processor::NOT_DONE) {
    
    //Do Qc Reset
    $visitObject->resetQC();
    
    //Log activity
    $actionDetails['type_visit']=$visitObject->visitType;
    $actionDetails['patient_code']=$visitObject->patientCode;
    $actionDetails['modality_visit']=$visitObject->visitGroupObject->groupModality;
    $actionDetails['reason']=$reason;
    Tracker::logActivity($_SESSION['username'], $_SESSION['role'], $visitObject->study, $id_visit, "Reset QC", $actionDetails);
    
    echo(json_encode(true));
    
} else {
    echo(json_encode(false));
}
