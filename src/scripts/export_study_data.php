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
 * Export database data relative to current study
 */

header('content-type: text/html; charset=utf-8');
require_once($_SERVER['DOCUMENT_ROOT'].'/vendor/autoload.php');

Session::checkSession();
$linkpdo=Session::getLinkpdo();

@Session::logInfo('Username : '.$_SESSION['username'].
	' Role: '.$_SESSION ['role'].' Study: '.$_SESSION['study']);


$userObject=new User($_SESSION['username'], $linkpdo);
$accessCheck=$userObject->isRoleAllowed($_SESSION['study'], $_SESSION['role']);

if ($accessCheck && $_SESSION['role'] == User::SUPERVISOR) {

	$studyObject=new Study($_SESSION['study'], $linkpdo);

	$exportObject=$studyObject->getExportStudyData();

	$patientCsvFile=$exportObject->exportPatientTable();

	$visitCsvFile=$exportObject->exportVisitTable();

	$orthancCsvFile=$exportObject->getImagingData();

	$reviewCsvFiles=$exportObject->getReviewData();
    
	$associatedFileZip=$exportObject->exportAssociatedFiles();
   
	//Output everything for download
	$date=Date('Ymd_his');
	header('Content-type: application/zip');
	header('Content-Disposition: attachment; filename="export_study_'.$_SESSION['study'].'_'.$date.'.zip"');
    
	//Final ZIP creation
	$zip=new ZipArchive;
	$tempZip=tempnam(ini_get('upload_tmp_dir'), 'TMPZIP_');
	$zip->open($tempZip, ZipArchive::CREATE);
	$zip->addFile($patientCsvFile, "export_patient.csv");
	$zip->addFile($visitCsvFile, "export_visits.csv");
	$zip->addFile($orthancCsvFile, "export_orthanc.csv");
	foreach ($reviewCsvFiles as $key=>$file) {
		$zip->addFile($file, "export_review_$key.csv");
	}
	$zip->addFile($associatedFileZip, "associatedFiles.zip");
	$zip->close();
    
    
	readfile($tempZip);
    
	//Delete Temp Files
	unlink($patientCsvFile);
	unlink($visitCsvFile);
	unlink($orthancCsvFile);
	unlink($tempZip);
	foreach ($reviewCsvFiles as $key=>$file) {
		unlink($file);
	}
    
} else {
	header('HTTP/1.0 403 Forbidden');
	die('You are not allowed to access this file.');
}