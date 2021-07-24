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
 * Process User's account modification
 */

Session::checkSession();
$linkpdo=Session::getLinkpdo();

if (isset($_SESSION['admin'])) {
    
	if (isset($_POST['confirmer'])) {
        
		//process the form
		//Get post data
		$status=$_POST['statut'];
		$old_status=$_POST['old_status'];
		$username=$_POST['username'];
		$last_name=$_POST['last_name'];
		$first_name=$_POST['first_name'];
		$email=$_POST['email'];
        
		$administrator=false;
		if (isset($_POST['administrator'])) {
			$administrator=true;
		}
        
		if (!isset($_POST['profile'])) {
			$role="@";
		}else {
			$role=$_POST['profile']; //format nameRole@nameStudy
		}
        
		$mainCenterCode=$_POST['main_center'];
        
		$newAffiliatedCenters=[];
		if (isset($_POST['afficheur_centre'])) {
			$newAffiliatedCenters=$_POST['afficheur_centre'];
		}
        
		$phone=$_POST['phone'];
		$job=$_POST['job'];
        
		$orthancAddress=$_POST['orthancAddress'];
		$orthancLogin=$_POST['orthancLogin'];
		$orthancPassword=$_POST['orthancPassword'];
        
		//Erase character that are not number
		$phone=preg_replace("/[^0-9]/", "", $phone);
        
		//Chech that data are complete and write to the database
		if (empty($_POST['username']) || empty($_POST['last_name']) || empty($_POST['first_name']) || empty($_POST['email']) || !is_numeric($_POST['main_center'])) {
			$answer="Form Uncomplete";
		}
		else if (!preg_match('/^[a-z0-9\-_.]+@[a-z0-9\-_.]+\.[a-z]{2,4}$/i', $email)) {
			$answer="Email Not Valid";
		}
        
		//Write to the database
		else {
            
			$userObject=new User($username, $linkpdo);
			$userObject->updateUser($last_name, $first_name, $email, $phone,
				$job, $status, $administrator, $mainCenterCode, $orthancAddress, $orthancLogin, $orthancPassword);
            
			//Update roles
			$array=[];
			if (isset($_POST['profile'])) {
				foreach ($role as $valeur) {
					// Split role/Study string into two values
					$valeur2=explode("@", $valeur);
					$array[$valeur2[1]][]=$valeur2[0];
				}
                
				$arrayBDD=$userObject->getRolesMap();
                
                
				$insert_roles=[];
				$delete_roles=[];
				//for common study, list all role to add and to remove
				$commonstudies=@array_intersect_assoc($arrayBDD, $array);
				foreach ($commonstudies as $etude=>$role) {
					$insert_roles[$etude]=array_diff($array[$etude], $arrayBDD[$etude]);
					$delete_roles[$etude]=array_diff($arrayBDD[$etude], $array[$etude]);
				}
				//Study that has no role anymore
				$studyRoleToDelete=@array_diff_assoc($arrayBDD, $array);
				//new studies with at least one role
				$studyRoleToCreate=@array_diff_assoc($array, $arrayBDD);
                
				//writing in database
				insertRoleToUser($userObject, $insert_roles);
				insertRoleToUser($userObject, $studyRoleToCreate);
				deleteRoleToUser($userObject, $delete_roles);
				deleteRoleToUser($userObject, $studyRoleToDelete);
			}
            
            
			//Update affiatied centers
			$array_center_BDD=$userObject->getAffiliatedCenters();
			if (empty($array_center_BDD)) {
				$array_center_BDD=[];
			}
            
			//Isolate difference between new values and existing value in DB
			$insert_centers=array_diff($newAffiliatedCenters, $array_center_BDD);
			$delete_centers=array_diff($array_center_BDD, $newAffiliatedCenters);
            
			//Add centers to User
			foreach ($insert_centers as $centerCode) {
				$userObject->addAffiliatedCenter($centerCode);
			}
            
			//Remove centers to User
			foreach ($delete_centers as $centerCode) {
				$userObject->removeAffiliatedCenter($centerCode);
			}
            
			//Log the activity
			$actionDetails['modified_user']=$username;
			$actionDetails['add_center_to_user']=$insert_centers;
			$actionDetails['remove_center_to_user']=$delete_centers;
			$actionDetails['user_lastname']=$last_name;
			$actionDetails['user_firstname']=$first_name;
			$actionDetails['email']=$email;
			$actionDetails['phone']=$phone;
			$actionDetails['main_center']=$mainCenterCode;
			$actionDetails['job']=$job;
			$actionDetails['status']=$status;
			$actionDetails['is_admin']=$administrator;
			$actionDetails['add_role_to_user']=@array_merge($insert_roles, $studyRoleToCreate);
			$actionDetails['remove_role_to_user']=@array_merge($delete_roles, $studyRoleToDelete);
			$actionDetails['orthanc_address']=$orthancAddress;
			$actionDetails['orthanc_login']=$orthancLogin;
			$actionDetails['orthan_password_length']=strlen($orthancPassword);
            
			Tracker::logActivity($_SESSION['username'], "Administrator", null, null, "Edit User", $actionDetails);
            
			//If new account status is unconfirmed (passord reset) , send the new password by email
			if ($status == USER::UNCONFIRMED) {
				$new_mdp=bin2hex(random_bytes(5));
				$userObject->setUnconfirmedAccount($new_mdp);
                
				$mail=new Send_Email($linkpdo);
				$mail->addEmail($email);
				$mail->sendModifyUserMessage($username, $new_mdp);
                
			}
            
			$answer="Success";
		}
        
		//Output answer for Ajax
		echo(json_encode($answer));
        
	}else {
        
		$username=$_GET['username'];
        
		$userObject=new User($username, $linkpdo);
		//Get all roles available in the database
		$availableStudies=Global_Data::getAllStudies($linkpdo, true);
		//Get all curent roles of the user (for all studies)
		$userRoles=$userObject->getRolesMap();
		//Get all job to display them in the selector
		$jobs=Global_Data::getAllJobs($linkpdo);
		//Get user main center
		$mainCenter=new Center($linkpdo, $userObject->mainCenter);
		//Get all available centers to display them in the selector
		$centers=Global_Data::getAllCentersObjects($linkpdo);
		//Get user's affiliated centers
		$usersAffiliatedCenters=$userObject->getAffiliatedCentersAsObjects();
        
		require 'views/administrator/modify_user_view.php';
	}
    
	
}else {
	require 'includes/no_access.php';
}


/**
 * Deletes speficied roles to user
 * @param $username
 * @param $hmap_diff_bdd
 * @return boolean
 */
function deleteRoleToUser(User $userObject, $hmap_diff_bdd) {
	try {
		foreach ($hmap_diff_bdd as $study => $arrayRole) {
			foreach ($arrayRole as $role) {
				$userObject->deleteRole($study, $role);
			} 
		}
	}catch (Exception $e) { }
}


/**
 * Add a role to user
 * @param $username
 * @param $hmap_diff_form
 * @return boolean
 */
function insertRoleToUser(User $userObject, $hmap_diff_form) {
	try {
		foreach ($hmap_diff_form as $study => $arrayRole) {
			foreach ($arrayRole as $role) {
				$userObject->addRole($role, $study);
			}
		}
	}catch (Exception $e) { }
}
