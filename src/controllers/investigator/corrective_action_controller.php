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
 **/

/**
 * Form for corrective action of the investigator (form and write in the database),
 * Auto load the result in the form and disabled it if corrective action already availble in database
 */

Session::checkSession();
$linkpdo=Session::getLinkpdo();

$username = $_SESSION ['username'];
$study = $_SESSION ['study'];
$role = $_SESSION ['role'];

$id_visit = $_POST ['id_visit'];
$type_visit = $_POST ['type_visit'];
$patient_num = $_POST ['patient_num'];

$userObject=new User($username, $linkpdo);
$visitAllowed=$userObject->isVisitAllowed($id_visit, $role);

if (isset ( $_SESSION ['username'] ) && $visitAllowed) {

    $visitObject=new Visit($id_visit, $linkpdo);
	
	//If form sent and current status awaiting corrective action, accept to write the value in the database
    if ( (isset ( $_POST ['corrective_action'] ) || isset ( $_POST ['no_corrective_action']) ) && $visitObject->stateQualityControl == 'Corrective Action Asked' && $role==User::INVESTIGATOR ) {

	    //Check Investigator have been validated before accepting the correction answer
        if($visitObject->stateInvestigatorForm != "Done"){
	        $answer="Form Missing";
	        echo(json_encode($answer));
	        return;
	        
	    }
	    $newSeries = false;
	    $formCorrected=false;
	    $correctiveActionDecision=false;
	    
		if ( isset($_POST['new_series']) ) {
		    $newSeries = true;
		}
		
		if ( isset($_POST ['information_corrected']) ) {
		    $formCorrected = true;
		}
		
		if ( isset($_POST ['corrective_action']) ) {
		    $correctiveActionDecision = true;
		} 
		
		//Write in the database
		$visitObject->setCorrectiveAction($newSeries, $formCorrected, $correctiveActionDecision, $_POST['other_comment'], $username);
		
		//Log Activity
		$actionDetails['new_series_uploaded']=$newSeries;
		$actionDetails['form_corrected']=$formCorrected;
		$actionDetails['other_comment']=$_POST['other_comment'];
		$actionDetails['corrective_action_done']=$correctiveActionDecision;
		Tracker::logActivity($username, $role, $_SESSION ['study'], $id_visit, "Corrective Action", $actionDetails);
		
		
		// Send notification email to all Controllers and Supervisors of the study
		$sendEmail = new Send_Email ($linkpdo);
		$sendEmail->addGroupEmails($visitObject->study, User::SUPERVISOR)
					->addGroupEmails($visitObject->study, User::CONTROLLER);
		$sendEmail->sendCorrectiveActionDoneMessage($correctiveActionDecision, $visitObject->study, $visitObject->patientCode, $visitObject->visitType);
		
		$answer="Success";
		echo(json_encode($answer));
		
	} else {
	    $studyObject=new Study($_SESSION['study'], $linkpdo);
	    require 'views/investigator/corrective_action_view.php';
	}
}else {
    require 'includes/no_access.php';
}
