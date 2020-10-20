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

require_once($_SERVER['DOCUMENT_ROOT'].'/rest/check_login.php');

$userObject=new User($username, $linkpdo);

$visitId=$_POST['visit_id'];
$fileKey=$_POST['file_key'];

//Need to retrieve study before testing permission, can't test visit permissions directly because permission class tests non deleted status
$visitObject=new Visit($visitId, $linkpdo);
$accessCheck=$userObject->isRoleAllowed($visitObject->study, User::REVIEWER);

if ( $accessCheck ) {
	$formProcessor=$visitObject->getFromProcessor(false, $username);

	if (!$formProcessor instanceof Form_Processor_File) {
		throw new Exception('Wrong From Processor type');
		return json_encode((false));
	}

	$filename=$_FILES['files']['name'];
	$fileMime=$_FILES['files']['type'];
	$tempFileLocation=$_FILES['files']['tmp_name'];
	$fileSize=$_FILES['files']['size'];
	try{
		$formProcessor->storeAssociatedFile($fileKey, $fileMime, $fileSize, $tempFileLocation);
		header('HTTP/1.0 200 OK');
	}catch (Throwable $t){
		header('HTTP/1.0 400 Bad Request');
	}

}else {
	header('HTTP/1.0 403 Forbidden');
	die('You are not allowed to access this file.');
}