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
 * Rest-API to download DICOM zipped of a visitId for Reviewer role
 * Deliver the ZIP only if visit is available for review and QC done
 */

require_once($_SERVER['DOCUMENT_ROOT'].'/rest/check_login.php');

// get posted data in a PHP Object
$data=json_decode(file_get_contents("php://input"), true);

$id_visit=$data['id_visit'];

//Get visit data of the requested visit
$visitObject=new Visit($id_visit, $linkpdo);

// Check Permissions of the calling user
$visitPermissions=$userObject->isVisitAllowed($id_visit, User::REVIEWER);

//If permission granted and visit active and review available and QC done
if ($visitPermissions) {
    
	//Get Array of Orthanc Series ID
	$resultatsIDs=$visitObject->getSeriesOrthancID();
	//Generate zip from orthanc and output it to the navigator
	$orthanc=new Orthanc();
	$zipStream=$orthanc->getZipStream($resultatsIDs);

	header("Content-Type: application/zip");
	header("Content-Transfer-Encoding: Binary");

	while (!$zipStream->eof()) {
		echo $zipStream->read(512);
	}
	
} else {
	header('HTTP/1.0 403 Forbidden');
	die('You are not allowed to access this file.'); 
}