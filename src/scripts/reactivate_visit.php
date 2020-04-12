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
 * Reset a visit as activated
 */

header('content-type: text/html; charset=utf-8');
require_once($_SERVER['DOCUMENT_ROOT'].'/vendor/autoload.php');

Session::checkSession();
$linkpdo=Session::getLinkpdo();

$userObject=new User($_SESSION['username'], $linkpdo);

//Need to retrieve study before testing permission, can't test visit permissions directly because permission class tests non deleted status
$visitObject=new Visit($_POST['visit_id'], $linkpdo);
$accessCheck=$userObject->isRoleAllowed($visitObject->study, $_SESSION['role']);

if ($accessCheck && $_SESSION['role'] == User::SUPERVISOR) {
	
	$answer=$visitObject->changeDeletionStatus(false);
	$actionDetails['visit_type']=$visitObject->visitType;
	$actionDetails['patient_code']=$visitObject->patientCode;
	$actionDetails['modality_visit']=$visitObject->visitGroupObject->groupModality;
	Tracker::logActivity($_SESSION['username'], $_SESSION['role'], $visitObject->study, $visitObject->id_visit, "Reactivate Visit", $actionDetails);
	//output the resulting answer
	echo(json_encode($answer));

} else {
	echo(json_encode(false));
}