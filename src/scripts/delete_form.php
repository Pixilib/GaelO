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
 * Delete the specified review (either local or not), handle visit status update
 */

require_once($_SERVER['DOCUMENT_ROOT'].'/vendor/autoload.php');

Session::checkSession();
$linkpdo=Session::getLinkpdo();

$id_review=$_POST['idReview'];
$reason=$_POST['reason'];

$userObject=new User($_SESSION['username'], $linkpdo);
$reviewObject=new Review($id_review, $linkpdo);
$visitObject=$reviewObject->getParentVisitObject();
$permissionsCheck=$userObject->isVisitAllowed($reviewObject->id_visit, User::SUPERVISOR);

//If supervisor session and permission OK
if ($_SESSION['role']==User::SUPERVISOR && $permissionsCheck) {
	
	//Fetch Data about the review / Visit we are going to delete
	if(!$reviewObject->deleted && !empty($reason)){
		
		try{
			$reviewObject->deleteReview();
			//Log activity
			$actionDetails['type_visit']=$visitObject->visitType;
			$actionDetails['patient_code']=$visitObject->patientCode;
			$actionDetails['modality_visit']=$visitObject->visitGroupObject->groupModality;
			$actionDetails['local_review']=$reviewObject->isLocal;
			$actionDetails['id_review']=$reviewObject->id_review;
			$actionDetails['reason']=$reason;
			Tracker::logActivity($_SESSION['username'], $_SESSION['role'], $_SESSION['study'], $reviewObject->id_visit, "Delete Form", $actionDetails);
			$answer=true;
		}catch(Throwable $t){
			error_log($t->getMessage());
			$answer=false;
		}
		
		//Notify the user that his form has been Deleted
		$email=new Send_Email($linkpdo);
		$email->addEmail($email->getUserEmails($reviewObject->username));
		$email->sendDeletedFormMessage($visitObject->study, $visitObject->patientCode, $visitObject->visitType);

	} else{
		$answer=false;
	}
	
	echo(json_encode($answer));
	
} else {
	echo(json_encode(false));
}
