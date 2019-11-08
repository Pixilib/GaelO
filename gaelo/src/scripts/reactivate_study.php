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
 * Reactivate a deleted study
 */

require_once($_SERVER['DOCUMENT_ROOT'].'/vendor/autoload.php');

Session::checkSession();
$linkpdo=Session::getLinkpdo();

$studyOrthancID=new Study_Details($_POST['studyOrthancId'], $linkpdo);

$userObject=new User($_SESSION['username'], $linkpdo);
$visitAllowed=$userObject->isVisitAllowed($studyOrthancID->idVisit, $_SESSION['role']);

if ($visitAllowed &&  $_SESSION['role']==User::SUPERVISOR ) {
    
    try{
        $studyOrthancID->changeDeletionStatus(false);
        //Log Activity
        $visitObject=new Visit($studyOrthancID->idVisit, $linkpdo);
        $visitObject->changeUploadStatus(Visit::DONE);
        $actionDetails['reactivated_study_orthancId']=$_POST['studyOrthancId'];
        $actionDetails['patient_code']=$visitObject->id_visit;
        $actionDetails['type_visit']=$visitObject->visitType;
        $actionDetails['Reason']=$_POST['reason'];
        Tracker::logActivity($_SESSION['username'], $_SESSION['role'], $_SESSION['study'], $studyOrthancID->idVisit, "Change Serie", $actionDetails);
        $answer=true;
    }catch (Exception $e){
        $answer=false;
        error_log($e);
    }

    echo(json_encode($answer));

} else {
    echo(json_encode(false));
}
