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
 * Export the users data of a study (or all users) in a CSV and output the file in download (function for admin only)
 */

header('content-type: text/html; charset=utf-8');
require_once($_SERVER['DOCUMENT_ROOT'].'/vendor/autoload.php');

Session::checkSession();
$linkpdo=Session::getLinkpdo();

if (isset($_SESSION['admin'])) {
	
	$study=$_SESSION['study'];
	$username=$_SESSION['admin'];
    
	if ($study == "All Studies") {
		//Select all users
		$userObjects=Global_Data::getAllUsers($linkpdo);
		//Build the CSV, header row and data
		$list[]=array('username', 'last name', 'first name', 'email', 'phone', 'country', 'creation date', 'last connexion', 'account status', 'main center code', 'main center name', 'job', 'number attempts', 'roles map', 'affiliated centers');
        
		foreach ($userObjects as $user) {
			$rolesMap=$user->getRolesMap();
			$userCenter=$user->getMainCenter();
			$list[]=array($user->username, $user->lastName, $user->firstName, $user->userEmail, $user->userPhone, $userCenter->countryName, $user->creationDateUser, $user->lastConnexionDate, $user->userStatus, $userCenter->code, $userCenter->name, $user->userJob, $user->loginAttempt, json_encode($rolesMap), json_encode($user->getAffiliatedCenters()));
		}
        
        
	}else {
		//Select users from the called study
		$studyObject=new Study($study, $linkpdo);
		$usersObjectArray=$studyObject->getUsersWithRoleInStudy();
		//Build the CSV, header row and data
		$list[]=array('username', 'last name', 'first name', 'email', 'phone', 'country', 'creation date', 'last connexion', 'account status', 'main center code', 'main center name', 'job', 'number attempts', 'roles in '.$study, 'affiliated centers');

		foreach ($usersObjectArray as $userObject) {
			$roles=$userObject->getRolesInStudy($study);
			$userCenter=$userObject->getMainCenter();
			$list[]=array($userObject->username, $userObject->lastName, $userObject->firstName, $userObject->userEmail, $userObject->userPhone, $userCenter->countryName, $userObject->creationDateUser, $userObject->lastConnexionDate, $userObject->userStatus, $userCenter->code, $userCenter->name, $userObject->userJob, $userObject->loginAttempt, implode("/", $roles), json_encode($userObject->getAffiliatedCenters()));
		}
	}
   
    
   
	$date=Date('Ymd_his');
	header('Content-type: text/csv');
	header('Content-Disposition: attachment; filename="export'.$date.'.csv"');
    
	$output=fopen('php://output', 'w');
    
	foreach ($list as $fields) {
		fputcsv($output, $fields);
	}
    
	//Output
	fclose($output);

    
}else {
	header('HTTP/1.0 403 Forbidden');
	die('You are not allowed to access this file.'); 
}