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
 * List all Visit waiting reviews for the user accross all studies
 */

require_once($_SERVER['DOCUMENT_ROOT'].'/rest/check_login.php');

$visitsResults=[];

$possibleStudyList=$userObject->getRolesMap();

foreach ($possibleStudyList as $study =>$roles){
    //In Each study with role consider studies where the user is reviewer
    if(in_array(User::REVIEWER, $roles)){
        
        //Get all awayting review visit for this user in this study and add details in the global list
        $studyObject=new Study($study, $linkpdo);
        $visitObjectArray=$studyObject->getStudySpecificGroupManager(Visit_Group::GROUP_MODALITY_PET)->getAwaitingReviewVisit($username);

        $patientsArray=[];

        foreach ($visitObjectArray as $visitObject){

            //Check if visit comes from a new patient
            if(  ! in_array($visitObject->patientCode, $patientsArray) ){

                $patientObject=$visitObject->getPatient();
                $groupObject=$patientObject->getPatientStudy()->getSpecificGroup(Visit_Group::GROUP_MODALITY_PET); 
                $patientVisitManager=$patientObject->getPatientVisitManager($groupObject);

                $patientCreatedVisits=$patientVisitManager->getCreatedPatientsVisits();

                foreach($patientCreatedVisits as $createdVisit){

                    //create a patient entry
                    $visitDetails['patientCode'] = $createdVisit->patientCode;
                    $visitDetails['idVisit']=$createdVisit->id_visit;
                    $visitDetails['visitType']=$createdVisit->visitType;
                    $visitDetails['visitStatus']=$createdVisit->reviewStatus;
                    
                    $dicomDetailsObject=$createdVisit->getStudyDicomDetails();
                    $visitDetails['studyDate']=$dicomDetailsObject->studyAcquisitionDate;
                    $visitDetails['studyUID']=$dicomDetailsObject->studyUID;

                }
                
            }

            $visitsResults[$study][]=$visitDetails;
        }
        
    }
    
}

header("Content-Type: application/json; charset=UTF-8");
echo(json_encode($visitsResults));