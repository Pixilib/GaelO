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
 * Return the downloadable series (either deleted or not depending on post request) that will be displayed
 * in datatable in the download manager
 */

header('content-type: text/html; charset=utf-8');
require_once($_SERVER['DOCUMENT_ROOT'].'/vendor/autoload.php');

Session::checkSession();
$linkpdo=Session::getLinkpdo();

$deleted=( $_POST['deleted'] === 'true' ? true : false);
$userObject=new User($_SESSION['username'], $linkpdo);
$permissionsCheck=$userObject->isRoleAllowed($_SESSION['study'], User::SUPERVISOR);

//If supervisor session and permission OK
if ($_SESSION['role']==User::SUPERVISOR && $permissionsCheck) {

    $studyObject=new Study($_SESSION['study'], $linkpdo);
    $uplodadedVisits=$studyObject->getStudySpecificGroupManager(Visit_Group::GROUP_MODALITY_PET)->getUploadedVisits();
    
    $json=[];
    
    foreach ($uplodadedVisits as $visitObject){
        $seriesObject=$visitObject->getSeriesDetails($deleted);
        
        $sumOfImages=0;
        $sumOfSize=0;
        $idList=[];
        
        if( !empty($seriesObject) ){
            foreach ($seriesObject as $serie){
                $sumOfImages+=$serie->numberInstances;
                $sumOfSize+=$serie->serieDiskSize;
                $idList[]=$serie->seriesOrthancID;
            }
            
            $patientObject=$visitObject->getPatient();
            $jsonObject['center'] = $patientObject->patientCenter;
            $jsonObject['code'] = $patientObject->patientCode;
            $jsonObject['withdraw'] = boolval($patientObject->patientWithdraw);
            $jsonObject['visit_type'] = $visitObject->visitType;
            $jsonObject['state_investigator_form'] =$visitObject->stateInvestigatorForm;
            $jsonObject['state_quality_control'] = $visitObject->stateQualityControl;
            $jsonObject['nb_series'] = count($seriesObject);
            $jsonObject['nb_instances'] = $sumOfImages;
            $jsonObject['Disk_Size'] = $sumOfSize;
            $jsonObject['orthancSeriesIDs'] = $idList;
            
            $json[] =$jsonObject;
        }
        
        
    }
    
    echo(json_encode($json));
    
}else{
    echo(json_encode("No Access"));
}