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
 * Forgot password request form, handles form display and processing to renew passord
 */

$linkpdo=Session::getLinkpdo();
//Processing form if sent
if (isset($_POST['send'])){
	
	$username=$_POST['username'];
	$email = $_POST['email'];
	
	$userObject=new User($username, $linkpdo);
	
	//If matching email (case insensitive comparison)
	if(strcasecmp($userObject->userEmail, $email) == 0){
	    
		if($userObject->userStatus == User::ACTIVATED || $userObject->userStatus== User::UNCONFIRMED || $userObject->userStatus== User::BLOCKED ){
	        
		$new_mdp = substr(uniqid(), 1,10);
		$userObject->setUnconfirmedAccount($new_mdp);
		
		//Log reset password event
		Tracker::logActivity($username, "User", null, null, "Ask New Password", null);

		// Send Email
		$sendEmail=new Send_Email($linkpdo);
		$sendEmail->addEmail($email);
		$sendEmail->sendNewPasswordEmail($username,$new_mdp);
		
		$answer="Success";

	   } else {
		
		//Get studies associated with account
        $linkedStudy=$userObject->getAllStudiesWithRole();
		
		$sendEmail=new Send_Email($linkpdo);
		$sendEmail->addAminEmails()->addEmail($email);
		$sendEmail->sendBlockedAccountNoPasswordChangeEmail($username, $linkedStudy); 
		
		$answer="Blocked";
		
	   }
	   
	} else {
	    $answer="Unknown";
    }
    
    echo(json_encode($answer));
    
//not data sent, form display
} else{
    require 'views/forgot_password_view.php';
}
