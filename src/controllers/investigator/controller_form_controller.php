<?php
/*Copyright (C) 2018 KANOUN Salim
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
 * Handle quality control form, handle form and processing and role management (disable form if non controller, fill it with existing result if quality controle done)
 */

Session::checkSession();
$linkpdo=Session::getLinkpdo();

$id_visit=$_POST['id_visit'];
$role=$_SESSION['role'];
$type_visit=$_POST['type_visit'];
$patient_num=$_POST['patient_num'];

$userObject=new User($_SESSION ['username'], $linkpdo);
$patientAllowed=$userObject->isVisitAllowed($id_visit, $role);

if (isset($_SESSION['username']) && $patientAllowed) {

    $visitObject=new Visit($id_visit, $linkpdo);

	//If form sended and controller role and quality controle not done, accept to store control quality result in database
    if( ( isset($_POST['refuse']) || isset($_POST['accept']) || isset($_POST['ask_corrective_action']) ) 
	       && $role == User::CONTROLLER 
           && $visitObject->uploadStatus ==Visit::DONE
           && ( $visitObject->stateQualityControl ==Visit::NOT_DONE || $visitObject->stateQualityControl ==Visit::QC_WAIT_DEFINITVE_CONCLUSION ) ) {
      
        $formAccepted=false;
        $imageAccepted=false;
        
        if ($_POST['formDecision'] == 'accepted') {
            $formAccepted=true;
        }
        if ($_POST['imageDecision'] == 'accepted') {
            $imageAccepted=true;
        }
        
        if (isset($_POST['accept'])) {
            $controlDecision=Visit::QC_ACCEPTED;
        }
        if (isset($_POST['refuse'])) {
            $controlDecision=Visit::QC_REFUSED;
        }
        if (isset($_POST['ask_corrective_action'])) {
            $controlDecision=Visit::QC_CORRECTIVE_ACTION_ASKED;
            //Make Investigator Form as Draft and update form status in visit
            $localReviewObject=$visitObject->getReviewsObject(true);
            $localReviewObject->unlockForm();
            $visitObject->changeVisitStateInvestigatorForm(Visit::LOCAL_FORM_DRAFT);
            
        }
        
        $visitObject->editQc($formAccepted, $imageAccepted, $_POST['formComment'], $_POST['imageComment'], $controlDecision, $_SESSION['username']);
        
        
        //Log action
        $actionDetails['patient_code']=$visitObject->patientCode;
        $actionDetails['type_visit']=$visitObject->visitType;
        $actionDetails['form_accepted']=$formAccepted;
        $actionDetails['image_accepted']=$imageAccepted;
        $actionDetails['form_comment']=$_POST['formComment'];
        $actionDetails['image_comment']=$_POST['imageComment'];
        $actionDetails['qc_decision']=$controlDecision;
        $actionDetails['qc_previous_status']=$visitObject->stateQualityControl;
        
        Tracker::logActivity($_SESSION ['username'], $role, $_SESSION ['study'], $id_visit, "Quality Control", $actionDetails);
        
        //Changing Boolean in text to send email
        if($formAccepted){
            $formAccepted="Accepted";
        }else{
            $formAccepted="Refused";
        }
        
        if($imageAccepted){
            $imageAccepted="Accepted";
        }else{
            $imageAccepted="Refused";
        }
        
        if(empty($_POST['formComment'])){
            $commentForm="N/A";
        }else{
            $commentForm=$_POST['formComment'];
        }
        
        if(empty($_POST['imageComment'])){
            $commentImage="N/A";
        }else{
            $commentImage=$_POST['imageComment'];
        }
        
        
        $message="Quality Control of the following visit has been set to : ".$controlDecision."<br>
                Patient Number:".$visitObject->patientCode."<br>
                Visit : ".$visitObject->visitType."<br>
                Investigation Form : ".$formAccepted." Comment :".$commentForm."<br>
                Image Series : ".$imageAccepted." Comment :".$commentImage." <br>";
        
        $email=new Send_Email($linkpdo);
        $email->setMessage($message);
        //List all supervisors of the study
        $supervisorsEmail=$email->getRolesEmails(User::SUPERVISOR, $visitObject->study);
        $monitorEmail=$email->getRolesEmails(User::MONITOR, $visitObject->study);
        $emailList=array_merge($supervisorsEmail,$monitorEmail);
        //Get users email
        $userEmail=$email->getUserEmails($_SESSION['username']);
        array_push($emailList, $userEmail);
        //Get users affiliated to a same center than patient
        $patientObject=$visitObject->getPatient();
        $patientCenter=$patientObject->getPatientCenter();
        $usersAffiliatedSameCenters=$patientCenter->getUsersAffiliatedToCenter($linkpdo, $patientCenter->code);
        //Select thoose who are investigators in the current study
        foreach ($usersAffiliatedSameCenters as $user){
        	$userRoles=$userObject->getRolesInStudy($_SESSION['study']);
            if(in_array(User::INVESTIGATOR, $userRoles)){
            	if(! in_array($user->userEmail, $emailList)) array_push($emailList, $user->userEmail);
            }
        }
        
        $email->sendEmail($supervisorsEmail, "Quality Control");
        
        //If Accepted inform the reviewers of the study by email
        if($controlDecision=="Accepted"){
          $email=new Send_Email($linkpdo);
          $message="Quality Control of the following visit has been ".$controlDecision."<br>
                Patient Number:".$visitObject->patientCode."<br>
                Visit : ".$visitObject->visitType."<br>
                The visit is Available for Review";
          $reviewersEmails=$email->getRolesEmails(User::REVIEWER, $visitObject->study);
          $email->setMessage($message);
          $email->sendEmail($reviewersEmails, "Awaiting Review");
          
        }

      //if send form with uncorrect permission, refuse
      } else if (isset($_POST['refuse']) || isset($_POST['accept']) || isset($_POST['ask_corrective_action'])
	           && $role != User::CONTROLLER
          && ( !in_array($visitObject->stateQualityControl, array(Visit::QC_NOT_DONE, Visit::QC_WAIT_DEFINITVE_CONCLUSION)) ) ){
           
          print("No Access");        
           
      }else{
        //if No form submitted, display the html form +- results
        $studyObject=new Study($_SESSION ['study'], $linkpdo);
        require 'views/investigator/controller_form_view.php';
	}
}else {
    require 'includes/no_access.php';
}
