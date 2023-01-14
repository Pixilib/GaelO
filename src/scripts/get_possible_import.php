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

require_once($_SERVER['DOCUMENT_ROOT'].'/vendor/autoload.php');

Session::checkSession();
$linkpdo=Session::getLinkpdo();

// Get Data of calling user
$userObject=new User($_SESSION['username'], $linkpdo);
$studyInvestigatorAllowed=$userObject->isRoleAllowed($_SESSION['study'], User::INVESTIGATOR);
$usercenters=$userObject->getInvestigatorsCenters();

if ($studyInvestigatorAllowed) {

	//Get Visits awaiting import from specified study/Visit Type
	$studyObject=new Study($_SESSION['study'], $linkpdo);
	$VisitArrayWaintingUpload=$studyObject->getAllAwaitingUploadImagingVisit();
    
	$availableVisits=[];
	//Add the studies name in an array
	foreach ($VisitArrayWaintingUpload as $visit) {
		$patientObject=$visit->getPatient();
		$patientCenter=$patientObject->getPatientCenter();
		//Check If patient center is included in user's centers before filling the answer table
		if (in_array($patientCenter->code, $usercenters)) {
			$patient['patientCode']=$patientObject->patientCode;
			$patient['patientFirstname']=$patientObject->patientFirstName;
			$patient['patientLastname']=$patientObject->patientLastName;
			$patient['patientSex']=$patientObject->patientGender;
			$patient['patientDOB']=$patientObject->patientBirthDateUS;
			$dateAcquisition=date('m-d-Y', strtotime($visit->acquisitionDate));
			$patient['visitModality']=$visit->getVisitGroup()->groupModality;
			$patient['visitDate']=$dateAcquisition;
			$patient['visitType']=$visit->visitType;
			$patient['visitTypeOrder']=$visit->visitTypeObject->visitOrder;
			$patient['visitTypeID']=$visit->visitTypeId;
			$patient['visitID']=intval($visit->id_visit);
			$patient['dicomConstraints']=$visit->getVisitCharacteristics()->getDicomContraintsArray();
			$availableVisits[]=$patient;
		}
	}
    
} else {
	$availableVisits=[];
}

echo(json_encode($availableVisits));