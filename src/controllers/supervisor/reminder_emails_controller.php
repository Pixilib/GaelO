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
 * Reminder Email function for non uploaded visits
 */

Session::checkSession();
$linkpdo=Session::getLinkpdo();

$userObject=new User($_SESSION['username'], $linkpdo);
$accessCheck=$userObject->isRoleAllowed($_SESSION['study'], $_SESSION['role']);

if ($accessCheck && $_SESSION['role'] == User::SUPERVISOR ) {
	$username = $_SESSION['username'];

    //If form sent process it
	if ( isset($_POST['validate']) && !empty($_POST['radioForm'])){
		
	    $emailSender=new Send_Email($linkpdo);
	    
		//Prepare list of Selected Jobs
		$jobs=null;
		if($_POST['radioFormRoles']=='investigator'){
			
			if(!empty($_POST['cra'])) $jobs[]="CRA";
			if(!empty($_POST['nurse'])) $jobs[]="Study nurse";
			if(!empty($_POST['nuclearist'])) $jobs[]="Nuclearist";
			if(!empty($_POST['radiologist'])) $jobs[]="Radiologist";
			if(!empty($_POST['pi'])) $jobs[]="PI";
			
		}else if($_POST['radioFormRoles']=='monitor'){
			$jobs=User::MONITOR;
		}else if($_POST['radioFormRoles']=='supervisor'){
			$jobs=User::SUPERVISOR;
		}
		
		$userText=$_POST['userText'];
		//Email for missing upload
		$studyObject=new Study($_SESSION['study'], $linkpdo);
		$sentEmails=0;
        if($_POST['radioForm'] == 'upload'){
                
                $visitMap=json_decode($studyObject->getAllPatientsVisitsStatus(),true);
                
                $resultsMapToSend=[];
    
                //Sort map by for missing visits by Center / patient / missing visit with calculated date range
                foreach ($visitMap as $visitName=>$patients){
                    
                    foreach ( $patients as $patientCode=>$patientDetails ) {
    
                        if($patientDetails['status']==Visit_Manager::SHOULD_BE_DONE){
                            $resultsMapToSend [$patientDetails['center']] [$patientCode] ["birthdate"]=$patientDetails['birthdate'];
                            $resultsMapToSend [$patientDetails['center']] [$patientCode] ["lastname"]=$patientDetails['lastname'];
                            $resultsMapToSend [$patientDetails['center']] [$patientCode] ["firstname"]=$patientDetails['firstname'];
                            $resultsMapToSend [$patientDetails['center']] [$patientCode] ["missing"] [$visitName] ['shouldBeDoneBefore']=$patientDetails['shouldBeDoneBefore'];
                            $resultsMapToSend [$patientDetails['center']] [$patientCode] ["missing"] [$visitName] ['shouldBeDoneAfter']=$patientDetails['shouldBeDoneAfter'];
                            
                        }
                        
                    }
                }
    
                foreach($resultsMapToSend as $center=>$patients){
                    //Create Table for each center in which we add all missing visits
                    $message = '<table><tr>
                    <th>Patient Number</th>
                    <th>Initials</th>
                    <th>Birthdate</th>
                    <th>Visit Name</th>
                    <th>Theorical Date</th>
                    </tr>';

                    foreach ( $patients as $patientCode=>$patientDetails ) {
                        
                        foreach ($patientDetails['missing'] as $visitName=>$details){

                            $message = $message.'<tr>
                            <td>'.$patientCode.'</td>
                            <td>'.$patientDetails['firstname'].$patientDetails['lastname'].'</td>
                            <td>'.$patientDetails['birthdate'].'</td>
                            <td>'.$visitName.'</td>
                            <td>'.'From '.$details['shouldBeDoneAfter'].' To '.$details['shouldBeDoneBefore'].'</td>
                            </tr>';

                        }

                    }
                    
                    $message = $message.'</table>';
                    
                    //Get emails account linked with the current center and respecting the role filter
                    $emails=selectDesinatorEmailsfromCenters($_SESSION['study'], $center, $jobs, $linkpdo);

                    //Send the email
                    $emailSender->setMessage($message);
                    $emailSender->sendEmail($emails, "Reminder : Missing Investigator Form", $emailSender->getUserEmails($username));
                    $sentEmails++;
                }
             
          	
            //Email for missing investigator form
            }else if ($_POST['radioForm'] == 'investigation') {
                
                $missingFormVisits=$studyObject->getVisitsMissingInvestigatorForm();
                
                $mailsByCenter=[];
                foreach ($missingFormVisits as $visit){
                    $relatedPatient=$visit->getPatient();
                    $center=$relatedPatient->getPatientCenter()->code;
                    $mailsByCenter[$center][$relatedPatient->patientCode][]=$visit->visitType;
                }
               
                //For each center build the message and send the email
                foreach ($mailsByCenter as $center=>$details){
                    
                    //Prepare message including all missing visits forms for this center
                    $message = $userText."<br> The investigation form has not been completed or validated for these visits.<br>
					<table>
					<tr>
					<td>Patient Code</td>
                    <td>Visit Name</td>
                    </tr>";
                    
                    foreach ($details as $patientCode=>$visitsType){
                        $message=$message.'<tr>
	                    <td>'.$patientCode.'</td>
	                    <td>'.implode(",", $visitsType).'</td>
	                    </tr>';
                    }
                    
                	$message=$message.'</table>';
                	//List the destinators matching center and jobs requirement
                	$emails=selectDesinatorEmailsfromCenters($_SESSION['study'],$center, $jobs, $linkpdo);
                	//Send the email
                	$emailSender->setMessage($message);
                	$emailSender->sendEmail($emails, "Reminder : Missing Investigator Form", $emailSender->getUserEmails($username));
                	$sentEmails++;
                }
                
              //Email for missing corrective Action 
              } else if ($_POST['radioForm'] == 'corrective') {
                  
                  $visitsCorrectiveActionAsked=$studyObject->getVisitWithQCStatus(Visit::QC_CORRECTIVE_ACTION_ASKED);
                  
                  $mailsByCenter=[];
                  foreach ($visitsCorrectiveActionAsked as $visitCorrective){
                      $relatedPatient=$visitCorrective->getPatient();
                      $centerCode=$relatedPatient->getPatientCenter()->code;
                      $mailsByCenter[$centerCode][$relatedPatient->patientCode][]=$visitCorrective->visitType;
                  }

                  //For each center build the message and send the email
                  foreach ($mailsByCenter as $center=>$details) {
                      
                    $message = $userText."<br> A corrective action was asked, thanks for doing the corrective action.<br>
					<table>
					<tr>
					<td>Patient Code</td>
                    <td>Visit Name</td>
                    </tr>";
                      
                    foreach ($details as $patientCode=>$visitsType){
                        $message=$message.'<tr>
	                    <td>'.$patientCode.'</td>
	                    <td>'.implode(",", $visitsType).'</td>
	                    </tr>';    
                    }
                  	
                  	$message=$message.'</table>';
                  	//List the destinators matching center and jobs requirement
                  	$emails=selectDesinatorEmailsfromCenters($_SESSION['study'], $center, $jobs, $linkpdo);
                  	//Send the email
                  	$emailSender->setMessage($message);
                  	$emailSender->sendEmail($emails, "Reminder : Missing Investigator Form", $emailSender->getUserEmails($username));
                  	$sentEmails++;
                  }
                
              }
              
              $answer['status']="Success";
              $answer['centerNb']=$sentEmails;
              echo(json_encode($answer));
              
              
              
    } else{
        //Display the send email form
        $studyObject=new Study($_SESSION['study'], $linkpdo);
        $visitMap=json_decode($studyObject->getAllPatientsVisitsStatus(),true);
        require 'views/supervisor/reminder_emails_view.php';
  }

}else {
    require 'includes/no_access.php';
}

function selectDesinatorEmailsfromCenters($study, $center, $job, $linkpdo){
	//Select All users that has a matching center
	$users =Center::getUsersAffiliatedToCenter($linkpdo, $center);
	$finalEmailList=[];
	//For each user check that we match role requirement (array if investigator), string if monitor or supervisor
	foreach ($users as $user){
	    
	    if( is_array($job) && !in_array($user->userJob, $job)){
            continue;
        }
	
        if($user->isRoleAllowed($study, User::INVESTIGATOR)){
            $finalEmailList[]=$user->userEmail;;
        }
		
	}
	
	return $finalEmailList;
}
