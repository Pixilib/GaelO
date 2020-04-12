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
 * Get user's details
 * Modify users detail by POST
 */

header('content-type: text/html; charset=utf-8');
require_once($_SERVER['DOCUMENT_ROOT'].'/vendor/autoload.php');

Session::checkSession();
$linkpdo=Session::getLinkpdo();

if ($_SESSION['username']) {

	if (!empty($_POST)) {

		$userObject=new User($_SESSION['username'], $linkpdo);

		if ($_SESSION['admin'] && isset($_POST['Username'])) {
			//Administrator updating data

		}else {
			//User updates it's own data
			$userObject->updateUser($_POST['LastName'], $_POST['FirstName'], $userObject->userEmail, $_POST['Phone'], $userObject->userJob,
									$userObject->userStatus, $userObject->isAdministrator, $userObject->mainCenter, $userObject->orthancAddress, 
									$userObject->orthancLogin, $userObject->orthancPassword);

            
		}

	}

	//Get Data and ouput them
	$userObject=new User($_SESSION['username'], $linkpdo);

	$answer['Username']=$userObject->username;
	$answer['LastName']=$userObject->lastName;
	$answer['FirstName']=$userObject->firstName;
	$answer['Email']=$userObject->userEmail;
	$answer['Phone']=$userObject->userPhone;

	echo(json_encode($answer));
   
    
}else {

	header('HTTP/1.0 403 Forbidden');
	die('You are not allowed to access this file.');

}