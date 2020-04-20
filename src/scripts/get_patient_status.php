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
  * Return all patients status for a given visit group + visit name
  */

header('content-type: text/html; charset=utf-8');
require_once($_SERVER['DOCUMENT_ROOT'].'/vendor/autoload.php');

Session::checkSession();
$linkpdo=Session::getLinkpdo();

$userObject=new User($_SESSION['username'], $linkpdo);
$permissionsCheck=$userObject->isRoleAllowed($_SESSION['study'], User::SUPERVISOR);

//If supervisor session and permission OK
if ($_SESSION['role'] == User::SUPERVISOR && $permissionsCheck) {

	$modality=$_POST['modality'];
	$visitName=$_POST['visit_type'];

	$studyObject=new Study($_SESSION['study'], $linkpdo);
	$visitTypeObject=$studyObject->getSpecificGroup($modality)->getVisitType($visitName);

	$studyVisitManager=$studyObject->getStudySpecificGroupManager($modality);
	$patientStatus=$studyVisitManager->getPatientVisitStatusForVisitType($visitTypeObject);
    
	echo(json_encode($patientStatus));
    
}else {
	echo(json_encode("No Access"));
}