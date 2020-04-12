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
 * Form for new user creation, handles form display and post processing
 */

Session::checkSession();
$linkpdo=Session::getLinkpdo();

if ($_SESSION['admin']) {

	if (isset($_POST['confirmer'])) {
		// if form sent, process it
		$username=$_POST['username'];
		$last_name=$_POST['last_name'];
		$first_name=$_POST['first_name'];
		$centers=[];
		if (isset($_POST['afficheur_centre'])) {
			$centers=$_POST['afficheur_centre']; // contains string array of affiliated centers
		}
		$email=$_POST['email'];
		$roles=[];
		if (isset($_POST['profile'])) {
			$roles=$_POST['profile'];
		}
		$mainCenter=$_POST['center'];
		$telephone=$_POST['phone'];
		$job=$_POST['job'];
		$administrator=false;
        
		$orthancAddress=$_POST['orthancAddress'];
		$orthancLogin=$_POST['orthancLogin'];
		$orthancPassword=$_POST['orthancPassword'];
        
		if (isset($_POST['administrator'])) {
			$administrator=true;
		}
        
		// Let only numbers for number phone
		$telephone=preg_replace("/[^0-9]/", "", $telephone);
		// Check from completion
		if (empty($_POST['username']) || empty($_POST['last_name']) || empty($_POST['first_name']) || empty($_POST['email']) || !is_numeric($_POST['center'])) {
			$answer="Form uncomplete";
		}else if (!preg_match('/^[a-z0-9\-_.]+@[a-z0-9\-_.]+\.[a-z]{2,4}$/i', $email)) {
			$answer="Wrong Email";
			// If OK write in the user database
		}else {
			try {
				$mdp=substr(uniqid(), 1, 10);
				User::createUser($username, $last_name, $first_name, $email,
					$telephone, $mdp, $job, $mainCenter, $administrator, $orthancAddress, $orthancLogin, $orthancPassword, $linkpdo);
                
				$userObject=new User($username, $linkpdo);
				// Add each user roles
				$addRoleMemoryForLog=[];
				foreach ($roles as $role) {
					$valeur2=explode("@", $role);
					$addRoleMemoryForLog[]=$valeur2;
					$roleToAdd=$valeur2[0];
					$study=$valeur2[1];
					$userObject->addRole($roleToAdd, $study);
				}
				// Add each affiliated center
				foreach ($centers as $centerCode) {
					if (!empty($centerCode)) {
						$userObject->addAffiliatedCenter($centerCode);
					}
				}
                
				// Log action
				$actionDetails['created_user']=$username;
				$actionDetails['user_lastname']=$last_name;
				$actionDetails['user_firstname']=$first_name;
				$actionDetails['email']=$email;
				$actionDetails['phone']=$telephone;
				$actionDetails['job']=$job;
				$actionDetails['main_center']=$mainCenter;
				$actionDetails['is_admin']=$administrator;
				$actionDetails['add_center_to_user']=$centers;
				$actionDetails['add_role_to_user']=$addRoleMemoryForLog;
				$actionDetails['orthanc_address']=$orthancAddress;
				$actionDetails['orthanc_login']=$orthancLogin;
				$actionDetails['orthan_password_length']=strlen($orthancPassword);
                
				Tracker::logActivity($_SESSION['username'], "Administrator", null, null, "Create User", $actionDetails);
                
				// Send confirmation message
				$mails=new Send_Email($linkpdo);
				$mails->addEmail($email);
				$mails->sendNewAccountMessage($username, $mdp);
                
				$answer="Success";
			}catch (exception $e1) {
				$answer=$e1->getMessage();
			}
            
		}
        
		// Output answer for Ajax
		echo (json_encode($answer));
        
	}else {

		// Get all possible roles for active studies
		$availableStudies=Global_Data::getAllStudies($linkpdo, true);
		// Get all possible jobs
		$jobs=Global_Data::getAllJobs($linkpdo);
		// Get all possible centers
		$centers=Global_Data::getAllCentersObjects($linkpdo);
        
		require 'views/administrator/new_user_view.php';
	}
    
}else {
	require 'includes/no_access.php';
}