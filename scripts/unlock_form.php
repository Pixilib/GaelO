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
 * Unlock the specified local form
 */

require_once($_SERVER['DOCUMENT_ROOT'].'/vendor/autoload.php');

Session::checkSession();
$linkpdo=Session::getLinkpdo();

$username=$_SESSION['username'];
$study=$_SESSION['study'];

$id_review = $_POST['idReview'];
$reason=$_POST['reason'];

$userObject=new User($username, $linkpdo);
$reviewObject=new Review($id_review, $linkpdo);
$permissionsCheck=$userObject->isVisitAllowed($reviewObject->id_visit, User::SUPERVISOR);

//If supervisor session and permission OK
if ($_SESSION['role']==User::SUPERVISOR && $permissionsCheck) {
		//Get data on the review / visit we are going to unlock

		$visitObject= $reviewObject->getParentVisitObject();
		try{
			//Unvalidate the form for unlock
			$reviewObject->unlockForm();
			$visitObject=$reviewObject->getParentVisitObject();
			//Log activity
			$actionDetails['type_visit']=$visitObject->visitType;
			$actionDetails['patient_code']=$visitObject->patientCode;
			$actionDetails['local_review']=$reviewObject->isLocal;
			$actionDetails['id_review']=$reviewObject->id_review;
			$actionDetails['reason']=$reason;
			Tracker::logActivity($username, $_SESSION['role'], $study, $visitObject->id_visit, "Unlock Form", $actionDetails);
			$answer=true;
		}catch (Exception $e){
			$answer=false;			
		}
		
		//Notify the user that his form has been unlocked
		$email=new Send_Email($linkpdo);
		$message="Your form sent for study ".$visitObject->study." ,patient ".$visitObject->patientCode." ,visit ".$visitObject->visitType." have been unlocked. <br>
                    You can now edit and resend a new version of this form <br>";
		$userEmail=$email->getUserEmails($reviewObject->username);
		$email->setMessage($message);
		$email->sendEmail($userEmail, "Form Unlocked");
		
		echo(json_encode($answer));

} else {
    echo(json_encode(false));
}
