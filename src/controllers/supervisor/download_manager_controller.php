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
 * Allows to download locallly bath or manual selection (ZIP) of Dicom from the orthanc server
 */

Session::checkSession();
$linkpdo=Session::getLinkpdo();

$userObject=new User($_SESSION['username'], $linkpdo);
$accessCheck=$userObject->isRoleAllowed($_SESSION['study'], $_SESSION['role']);

if ($accessCheck && $_SESSION['role'] == User::SUPERVISOR ) {
    //If form sent, post process it
    if(! empty($_POST['qc']) ){
            //Build SQL query depending of checkbox
            
            $sql="SELECT orthanc_series.Series_Orthanc_ID FROM orthanc_studies,orthanc_series, patients, visits 
                    WHERE orthanc_studies.id_visit=visits.id_visit 
                    AND visits.patient_code=patients.code 
                    AND patients.study=:study
                    AND visits.deleted=0
					AND orthanc_studies.deleted=0
                    AND orthanc_series.Study_Orthanc_ID=orthanc_studies.Study_Orthanc_ID
                    AND orthanc_series.deleted=0";
            
            if ( ! isset($_POST["allcenters"]) ){
                $sql= $sql."AND (";
                $numItems = count($_POST['centers']);
                $i = 0;
                foreach($_POST['centers'] as $code){
                    $sql=$sql.'patients.center='.$code;
                    if( ++$i === $numItems) {
                        $sql=$sql.") ";
                    }
                    else{
                        $sql=$sql." OR ";
                    }
                }
            }
            if( ! isset($_POST["allpatients"]) ){
                if ($_POST["patients"] == "Included") $sql= $sql."AND patients.withdraw=0 ";
                else if($_POST["patients"] == "Withdrawn") $sql= $sql."AND patients.withdraw=1 ";
                
            }
            
            if( $_POST["qc"]!="All"){
                $sql= $sql."AND visits.state_quality_control='Accepted' ";
            }
            if( !isset($_POST["allvisits"]) ){
                $sql= $sql."AND (";
                $numItems = count($_POST['visits']);
                $i = 0;
                foreach($_POST['visits'] as $visit){
                   
                    $sql=$sql."visits.visit_type="."'".$visit."'";
                    if( ++$i === $numItems) {
                        $sql=$sql.") ";
                    }
                    else{
                        $sql=$sql." OR ";
                    }
                }
            }
            
            //Get Orthanc IDs of selected visits
            $orthancIDfetcher = $linkpdo->prepare($sql);
            $orthancIDfetcher->execute(array('study'=>$_SESSION['study']));
            $orthancIDs= $orthancIDfetcher->fetchAll(PDO::FETCH_ASSOC);
            $id=[];
            foreach ($orthancIDs as $value){
                $id[]=$value['Series_Orthanc_ID'];
            }
            
            //Return IDs in JSON array (will be handled by Ajax)
            echo(json_encode($id));
            
    //Prepare and display general form for global downalod       
    }else{
        $study=$_SESSION['study'];
        
        //Get all possible visits in the current study
        $studyObject=new Study($study, $linkpdo);
        $visits=$studyObject->getAllPossibleVisitTypes();
        
        //Select all unique centers in which at least one patient is included in the current study
        $patientObjects=$studyObject->getAllPatientsInStudy();
        $centers=[];
        foreach ($patientObjects as $patient){
        	$patientCenter=$patient->getPatientCenter();
        	if(!array_key_exists($patient->patientCenter, $centers)){
        		$centers[$patient->patientCenter]=$patientCenter;
        	}
        }
        
        $orthanc = new Orthanc();
        $usersInStudy=$studyObject->getAllRolesByUsers();
        require 'views/supervisor/download_manager_view.php';
        
    }
     
}else{
    require 'includes/no_access.php';
}
  