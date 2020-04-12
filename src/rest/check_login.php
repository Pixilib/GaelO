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
 * Test if credentatial are correct and output the login test results
 * This script is included in all other Rest API for security check
 * If access not allwoed throw an HTTP error
 */

require_once($_SERVER['DOCUMENT_ROOT'].'/vendor/autoload.php');

if (!isset($_SERVER['PHP_AUTH_USER'])) {
	header('WWW-Authenticate: Basic realm="Gaelo"');
	header('HTTP/1.0 401 Unauthorized');
	exit;
    
}

$username=$_SERVER['PHP_AUTH_USER'];
$password=$_SERVER['PHP_AUTH_PW'];

$linkpdo=Session::getLinkpdo();

$userObject=new User($username, $linkpdo);
$passwordCheck=$userObject->isPasswordCorrectAndActivitedAccount($password);

if(!$passwordCheck){
    
	if(!$userObject->isExistingUser){
		header('HTTP/1.1 420 Non existing user');
        
	}else if(!$userObject->passwordCorrect && $userObject->userStatus == User::ACTIVATED && $userObject->passwordDateValide){
		header('HTTP/1.1 420 Wrong Password '.$userObject->loginAttempt."/3 wrong attempt");
        
	} else if(!$userObject->passwordDateValide){
		header('HTTP/1.1 420 Outdated Password, go to website to renew it');
        
	} else if($userObject->userStatus != User::ACTIVATED){
		header('HTTP/1.1 420 Account '.$userObject->userStatus);
	}
    
	exit();
    
}else{
	header("HTTP/1.1 200 OK");
}
