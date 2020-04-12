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
 * Send reviewers reminders for reviews awaiting conclusion
 * Recieve the visit map status from the reviewer manager
 */

header('content-type: text/html; charset=utf-8');
require_once($_SERVER['DOCUMENT_ROOT'].'/vendor/autoload.php');

Session::checkSession();
$linkpdo=Session::getLinkpdo();

$userObject=new User($_SESSION['username'], $linkpdo);
$accessCheck=$userObject->isRoleAllowed($_SESSION['study'], $_SESSION['role']);

if ($accessCheck && $_SESSION['role'] == "Supervisor" ) {
    
	$reviewdetailsMap=json_decode($_POST['reviewMap'],true);

	$emailList=[];
	$answer['nbReviewEmailed']=0;
	foreach($reviewdetailsMap as $key => $value){
		//If Review conclusion not reached
		if($value['reviewStatus']!= "Done" ){
			$missingReviewers=$value['reviewNotDoneBy'];
            
			foreach ($missingReviewers as $reviewer){
				$missingReview['patientNumber']=$value['patientNumber'];
				$missingReview['visit']=$value['visit'];
				$missingReview['acquisitionDate']=$value['acquisitionDate'];
				$missingReview['reviewStatus']=$value['reviewStatus'];
				//Add an entry for each reviewer with missing visit in it
				$answer['nbReviewEmailed']++;
				$emailList[$reviewer][]=$missingReview;
			}
		}
        
	}
    
	$message="The following visits are awaiting for your review to reach a final conclusion <br>";
	$emailSender=new Send_Email($linkpdo);
    
    
	$answer['nbReviewersEmailed']=0;
	//Prepare and send email for each reviewer having missing reviews
	foreach ($emailList as $user=>$missingReviews){
        
	   $table="<table>
                <thead>
    				<tr>
    					<th>Patient Number</th>
    					<th>Visit Name</th>
    					<th>Acquisition Date</th>
    					<th>Review Status</th>
    				</tr>
                </thead>
			    <tbody>";
       
		foreach ($missingReviews as $missingReview){
			$table=$table."<tr>
            					<th>".$missingReview['patientNumber']."</th>
            					<th>".$missingReview['visit']."</th>
            					<th>".$missingReview['acquisitionDate']."</th>
            					<th>".$missingReview['reviewStatus']."</th>
    				       </tr>";
            
		}
		$table=$table."</tbody></table>";
        
		$emailSender->setMessage($message.$table);
		$emailSender->setSubject("Awaiting Review for ".$_SESSION['study']);
		$emailSender->addEmail($emailSender->getUserEmails($user));
		$emailSender->sendEmail();
		$answer['nbReviewersEmailed']++;
        
	}
    
	$answer['status']="Success";
	echo(json_encode($answer));
    
}else{
	$answer['status']="No Access";
	echo(json_encode($answer));
}