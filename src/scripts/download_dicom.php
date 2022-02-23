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
 * recive array of OrthancID in post, generate ZIP of DICOM and push it to the browser download
 * used by supervisor (download manager) and Reviewer (download a visit dicom)
 */

require_once($_SERVER['DOCUMENT_ROOT'].'/vendor/autoload.php');

Session::checkSession();
$linkpdo=Session::getLinkpdo();

isset($_POST['id_visit']) ? $logIdVisit=$_POST['id_visit'] : $logIdVisit='N/A';
isset($_POST['json']) ? $askedJson=$_POST['json'] : $askedJson='N/A';

@Session::logInfo('Username : '.$_SESSION['username'].
	' Role: '.$_SESSION ['role'].' Study: '.$_SESSION['study'].' Visit ID: '.$logIdVisit.' Asked IDs: '.$askedJson);

$userObject=new User($_SESSION['username'], $linkpdo);

//Permission check, different level check if supervisor or reviewer 

if ($_SESSION['role'] == User::SUPERVISOR) {
	$permissionCheck=$userObject->isRoleAllowed($_SESSION['study'], $_SESSION['role']);
	$postdata=$_POST['json'];
	$json=json_decode($postdata, true);
	//SK ICI VERIFIER QUE LES id SONT BIEN DE L ETUDE AVEC LES DROITS ? Securite
	$ids=$json['json'];
}
else if ($_SESSION['role'] == User::REVIEWER) {
	$permissionCheck=$userObject->isVisitAllowed($_POST['id_visit'], $_SESSION['role']);
	$visitObject=new Visit($_POST['id_visit'], $linkpdo);
	$ids=$visitObject->getSeriesOrthancID();
    
}else if ($_SESSION['role'] == User::CONTROLLER) {
	$permissionCheck=$userObject->isVisitAllowed($_POST['id_visit'], $_SESSION['role']);
	$visitObject=new Visit($_POST['id_visit'], $linkpdo);
	if (in_array($visitObject->qcStatus, array(Visit::QC_NOT_DONE, Visit::QC_WAIT_DEFINITVE_CONCLUSION))) {
		$ids=$visitObject->getSeriesOrthancID();
	}
}else if ($_SESSION['role'] == User::INVESTIGATOR) {
	$permissionCheck=$userObject->isVisitAllowed($_POST['id_visit'], $_SESSION['role']);
	$visitObject=new Visit($_POST['id_visit'], $linkpdo);
	if ($visitObject->uploadStatus == Visit::DONE) {
		$ids=$visitObject->getSeriesOrthancID();
	}

}

if ($permissionCheck && count($ids) > 0) {

	//Download dicom corresponding to called ID with Orthanc APIs
	$orthanc=new Orthanc();
	
	$zipStream=$orthanc->getZipStream($ids);
	
	header("Content-Type: application/zip");
	header("Content-Transfer-Encoding: Binary");
	
	//For supervisor generic file name as the zip can merge visits
	if ($_SESSION['role'] == User::SUPERVISOR) {
		$date=Date('Ymd_his');
		header('Content-Disposition: attachment; filename="Dicom-'.$_SESSION['study'].'_'.$date.'.zip"');
	//For reviewer file name is identified by study_visit
	}else {
		$name=$_SESSION['study'].$visitObject->visitType;
		header('Content-Disposition: attachment; filename="Dicom'.$name.'.zip"');
	}

	while (!$zipStream->eof()) {
		echo $zipStream->read(2048);
	}

}else {
	header('HTTP/1.0 403 Forbidden');
	die('You are not allowed to access this file.'); 
}