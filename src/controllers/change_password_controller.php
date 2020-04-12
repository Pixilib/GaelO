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

Session::checkSession();
$linkpdo=Session::getLinkpdo();

/**
 * Form to change password, handles form display and processing
 */

//Password change allowed only for "temp" session (connexion of an unconfirmed account)
if (isset($_SESSION['temp'])) {
    
	if (isset($_POST['confirmer'])) {
	    
		$username=$_SESSION['temp'];
		$mdp1=$_POST['mdp1'];
		$mdp2=$_POST['mdp2'];
		$old_password=$_POST['old_password'];
		
		//Get current password and two previous password from database
		$userObject=new User($username, $linkpdo);
		
		//Store three last password
		$currentPassword=$userObject->password;
		$twoPreviousPassword[]=$userObject->previousPassword1;
		$twoPreviousPassword[]=$userObject->previousPassword2;
		$currentTempPassword=$userObject->tempPassword;

		
		//Check that reported old password is correct, if unconfirmed we use the temp password value
		if($userObject->userStatus == User::UNCONFIRMED){
			$checkCurrentPassword=password_verify($old_password, $currentTempPassword);
		}else{
			$checkCurrentPassword=password_verify($old_password, $currentPassword);
		}

		if($mdp1 != $mdp2){
			$answer['result']="DifferentPassword" ;
				
		} else if( !$checkCurrentPassword ) {
			$answer['result']="WrongOldPassword" ;
			
		} else if (strlen($mdp1)< 8 || preg_match('/[^a-z0-9]/i', $mdp1) || strtolower($mdp1) == $mdp1) {
			$answer['result']="IncorrectFormat" ;
			
		} else if (password_verify($mdp1, $currentTempPassword) || password_verify($mdp1, $currentPassword) || password_verify($mdp1, $twoPreviousPassword[0]) || password_verify($mdp1, $twoPreviousPassword[1]) ) {
			$answer['result']="SamePrevious" ;
			
		} else if($mdp1==$mdp2 && strtolower($mdp1) != $mdp1 && $checkCurrentPassword && !password_verify($mdp1, $currentTempPassword) && !password_verify($mdp1, $twoPreviousPassword[0]) && !password_verify($mdp1, $twoPreviousPassword[1]) && !password_verify($mdp1, $currentPassword) ){
				$answer['result']="OK" ;
				//Update the database with new password
				$userObject->updateUserPassword($mdp1, User::ACTIVATED);

				//Log Change Password action
				Tracker::logActivity($username, "User", null, null, "Change Password", "Password Changed");
		}

		echo(json_encode($answer));
		
	} else{
	    
		require 'views/change_password_view.php';
	    
	}
	
} else {
	require 'includes/no_access.php';
}
