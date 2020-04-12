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
 * Messenger Utility for messaging across platform, send message by emails
 * to user role group or individual user having a role the study
 */

Session::checkSession();
$linkpdo=Session::getLinkpdo();

if (isset($_SESSION['username'])) {
    
	if(!empty($_POST) && !empty($_SESSION['study'])){
        
		$individualUsers=array();
		if (isset($_POST['destinatorsList'])){
			$individualUsers=$_POST['destinatorsList'];
		}
        
		$rolesGroups=array();
		if(isset($_POST['destinatorsRoles'])){
			$rolesGroups=$_POST['destinatorsRoles'];
		}
        
		$message=$_POST['messageText'];
        
		$sendEmail = new Send_Email($linkpdo);
        
		foreach ($rolesGroups as $role){
            
			if($role!=User::ADMINISTRATOR){
				$sendEmail->addGroupEmails( $_SESSION['study'], $role);
			}else{
				$sendEmail->addAminEmails();            
			}
            
		}
        
		foreach ($individualUsers as $user){
			$sendEmail->addEmail($sendEmail->getUserEmails($user)); 
		}

		$userObject=new User($_SESSION['username'], $linkpdo);
		$sendEmail->setMessage ( $message );
		$sendEmail->setSubject('Message From '.$userObject->firstName.' '.$userObject->lastName);
		$sendEmail->sendEmail ();
        
		$actionDetails['destinators']=json_encode($sendEmail->emailsDestinators);
		$htmlMessageObject = new \Html2Text\Html2Text($message);
		$actionDetails['message']=$htmlMessageObject->getText();
        
		Tracker::logActivity($_SESSION['username'], "User", $_SESSION['study'], null, "Send Message", $actionDetails);
        
		echo(json_encode(true));
        
	}else if (!empty($_SESSION['study'])){
        
		//list all users having a role a in the study
		$studyObject=new Study($_SESSION['study'], $linkpdo);
		$usersObjects= $studyObject->getUsersWithRoleInStudy();
		//Sort by Lastname
		usort($usersObjects, function(User $a, User $b)
		{
			return strcmp($a->lastName, $b->lastName);
		});
        
		require 'views/messenger_view.php';
        

	}else{
		require 'includes/no_access.php';
	}
    
}else{
	require 'includes/no_access.php';
}