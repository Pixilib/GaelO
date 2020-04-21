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

header('content-type: application/json; charset=utf-8');
require_once($_SERVER['DOCUMENT_ROOT'].'/vendor/autoload.php');

Session::checkSession();
$linkpdo=Session::getLinkpdo();

$userObject=new User($_SESSION['username'], $linkpdo);

$visitId=$_POST['visit_id'];
$fileKey=$_POST['file_key'];
$local=$_SESSION['role'] == User::INVESTIGATOR ? true : false; 

//Need to retrieve study before testing permission, can't test visit permissions directly because permission class tests non deleted status
$visitObject=new Visit($visitId, $linkpdo);
$accessCheck=$userObject->isRoleAllowed($visitObject->study, $_SESSION['role']);

if ($accessCheck && in_array($_SESSION['role'], array(User::INVESTIGATOR, User::REVIEWER))) {
	$formProcessor=$visitObject->getFromProcessor($local, $_SESSION['username']);

	if (!$formProcessor instanceof Form_Processor_File) {
		throw new Exception('Wrong From Processor type');
		return json_encode((false));
	}
    
	//SK ICI TRAITER LE FILE OBJECT
	error_log(print_r($_FILES, true));

	$filename=$_FILES['files']['name'][0];
	$fileMime=$_FILES['files']['type'][0];
	$tempFileLocation=$_FILES['files']['tmp_name'][0];
	$fileSize=$_FILES['files']['size'][0];
	try{
		$formProcessor->storeAssociatedFile($fileKey, $fileMime, $fileSize, $tempFileLocation);
		echo( json_encode((true)) );
	}catch (Throwable $t){
		echo (json_encode((false)) );
	}

}else {
	header('HTTP/1.0 403 Forbidden');
	die('You are not allowed to access this file.');
}