<?php
/**
 Copyright (C) 2018-2020 KANOUN Salim
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
 * Display connexion form hand check access permission
 */

if (session_status() == PHP_SESSION_NONE) session_start();

//If session already opened, redirect to main
if (isset($_SESSION['username'])) {
	header("Refresh:0;url=/main");
}
//If form sent, process it
else if (!empty($_POST['formSent'])) {
   
	$linkpdo=Session::getLinkpdo();
	
	$userObject=new User($_POST['username'], $linkpdo);
	$connexionPermission=$userObject->isPasswordCorrectAndActivitedAccount($_POST['mdp']);
	
	//if connexion allowed
	if ($connexionPermission) {

		//if admin, open admin session boolean
		if ($userObject->isAdministrator) {
			$_SESSION['admin']=true;
			//Alert all admins of this connexion
			$email=new Send_Email($linkpdo);
			$email->addAminEmails();
			$email->sendAdminLoggedAlertEmail($_POST['username'], $_SERVER['REMOTE_ADDR']);

		}else {
			$_SESSION['admin']=false;
		}

		//open user session
		$_SESSION['username']=$_POST['username'];
		
		//Json message return user session opened
		$result['result']="user";
		
		//If not allowed, action depend on reason
	}else {
		//Case outdated password or unconfirmed status, open temp session to redirect to change password
		if ($userObject->passwordCorrect && (!$userObject->passwordDateValide || $userObject->userStatus == "Unconfirmed")) {
			//mot de passe non valide, on le change
			$_SESSION['temp']=$_POST['username'];
			$result['result']="temporary";
			$result['isPasswordDateValid']=$userObject->passwordDateValide;
		//case unactivated or blocked account
		}else if ($userObject->passwordCorrect && ($userObject->userStatus != null && $userObject->userStatus != "Activated")) {
			$result['result']=$userObject->userStatus;	
		//case non existing user
		}else if (!$userObject->isExistingUser) {
			$result['result']="unknown";
		//Case wrong password
		}else {
			//if too much tentative, account blocked
			if ($userObject->loginAttempt > 2) {
				$result['result']="NowBlocked";
			}else {
				$result['result']="WrongPassword";
				$result['attempt']=$userObject->loginAttempt;
			}

		}
	}
	//Echo answer for Ajax
	echo(json_encode($result));
//No data sent, display the form and it's script
}else {
    
	try {
		Session::getLinkpdo();
	}catch (Exception $e) {
		error_log("Can't Connect DB");
	}
    
	require 'views/index_view.php';
}
