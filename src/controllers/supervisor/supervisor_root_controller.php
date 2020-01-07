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
 * Root page for supervisor role
 */

Session::checkSession(true, true);
$linkpdo = Session::getLinkpdo();

$userObject= new User($_SESSION['username'], $linkpdo);
$permissionCheck = $userObject->isRoleAllowed($_POST['etude'], User::SUPERVISOR);

if ($permissionCheck && $_POST['role'] == User::SUPERVISOR) {
	//If Ok allow study and role and write the session variable
	$_SESSION['study'] = $_POST['etude'];
	$_SESSION['role'] = $_POST['role'];
	$studyObject = new Study($_SESSION['study'], $linkpdo);
	
	require 'views/supervisor/supervisor_root_view.php';

} else {
    require 'includes/no_access.php';
}

/**
 * Function to generate datatable JSON for visit status visualization
 * @param $study
 * @param $linkpdo
 * @return string
 */
function make_Json(Study $studyObject)
{
	$json = [];
	$activeVisitsArray = $studyObject->getAllCreatedVisits(false);

	foreach ($activeVisitsArray as $visitObject) {
		$patientObject = $visitObject->getPatient();
		$jsonObject['center'] = $patientObject->getPatientCenter()->code;
		$jsonObject['code'] = "<a onclick='linkPatientInfos(" . $patientObject->patientCode . ")' href='javascript:void(0);'>" . $patientObject->patientCode . "</a>";
		if (!$patientObject->patientWithdraw) {
			$jsonObject['withdraw'] = "Included";
		} else {
			$jsonObject['withdraw'] = "Withdrawn";
		}
		$jsonObject['visit_modality'] = $visitObject->visitGroupObject->groupModality;
		$jsonObject['visit_type'] = "<a onclick='linkVisitInfos(" . $visitObject->id_visit . ")' href='javascript:void(0);'>" . $visitObject->visitType . "</a>";
		$jsonObject['status_done'] = $visitObject->statusDone;
		$jsonObject['upload_status'] = $visitObject->uploadStatus;
		$jsonObject['state_investigator_form'] = $visitObject->stateInvestigatorForm;
		$jsonObject['state_quality_control'] = $visitObject->stateQualityControl;
		$jsonObject['review'] = $visitObject->reviewStatus;

		$json[] = $jsonObject;
	}

	return json_encode($json);
}