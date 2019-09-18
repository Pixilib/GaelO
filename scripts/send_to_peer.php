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
 * Send the selected study to the selected users (ex : to send to reviewers)
 */

header( 'content-type: text/html; charset=utf-8' );
require_once($_SERVER['DOCUMENT_ROOT'].'/vendor/autoload.php');

Session::checkSession();
$linkpdo=Session::getLinkpdo();

$userObject=new User($_SESSION['username'], $linkpdo);
$accessCheck=$userObject->isRoleAllowed($_SESSION['study'], $_SESSION['role']);

if ($accessCheck && $_SESSION['role'] == User::SUPERVISOR ) {
    
    $selectedUsers=$_POST['selectedUsers'];
	$ids=json_decode($_POST['json']);
	
	$orthanc = new Orthanc();
	foreach ($selectedUsers as $username){
	    //For each target user add Peer in Orthanc and push an async peer request
	    $userObject=new User($username, $linkpdo);
	    $orthanc->addPeer($username, $userObject->orthancAddress, $userObject->orthancLogin, $userObject->orthancPassword);
	    $jobAnswer["answer"]=json_decode($orthanc->sendToPeerAsyncWithAccelerator($username, $ids, false));
	    $jobAnswer["username"]=$username;
	    $results[]=$jobAnswer;
	}
	
	//SK EN Async fait plancer le tranfert
	//$orthanc->removeAllPeers();
	
	echo(json_encode($results));
	
}else{
    echo(json_encode(false));
}