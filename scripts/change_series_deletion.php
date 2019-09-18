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
 * Delete a serie from Orthanc after checking permission 
 * Orthanc serie is deleted, all related information on study is reset in DB and rebuilt from remaining data in Orthanc
 */

require_once($_SERVER['DOCUMENT_ROOT'].'/vendor/autoload.php');

Session::checkSession();
$linkpdo=Session::getLinkpdo();

if (isset($_SESSION['username']) ) {

    $username=$_SESSION['username'];
    $role=$_SESSION['role'];
    $seriesOrthancID = $_POST['seriesOrthancId'] ;
    $reason=$_POST['reason'];
    $action=$_POST['action'];
    
    $seriesOrthancIDToChange=[];
    $visitObject=null;
    
    if( strpos($seriesOrthancID, 'allVisit') !== FALSE && $action=='delete'){
        //If all visits get all series UID of this visit
        $id_visit=intval(substr($seriesOrthancID, 8));
        $visitObject=new Visit($id_visit, $linkpdo);
        $seriesObjectArray=$visitObject->getSeriesDetails();
        
        foreach ($seriesObjectArray as $serie){
            $seriesOrthancIDToChange[]=$serie->seriesOrthancID;
           
        }  
        
    }else{
        //Store the seriesOrthancID to Delete and it's ParentVisit ID
        $seriesObject=new Series_Details($seriesOrthancID, $linkpdo);
        $seriesOrthancIDToChange[]=$seriesObject->seriesOrthancID;
        $visitObject=new Visit($seriesObject->parentIdVisit, $linkpdo);
        $id_visit=$seriesObject->parentIdVisit;
    }
    
    //Check that visit related to series are allowed for the calling users
    $userObject=new User($username, $linkpdo);
    $visitAllowed=$userObject->isVisitAllowed($visitObject->id_visit, $_SESSION['role']);
    
    //Check Permissions
    if ( $visitAllowed && ($role==User::CONTROLLER|| $role==User::INVESTIGATOR || $role==User::SUPERVISOR) ){
        
        $changedArrayResult=[];
        
        foreach ($seriesOrthancIDToChange as $serieOrthancID){
            $seriesObject=new Series_Details($serieOrthancID, $linkpdo);
            
            if( in_array($visitObject->stateQualityControl, array(Visit::QC_ACCEPTED, Visit::QC_REFUSED)) ){
                //QC is terminated, Delete is Forbidden, return
                print("Deletion not authorized");
                return;
            }
            if($action=='delete'){
                $seriesObject->changeDeletionStatus(true);
                
                //Check still available series in this visit
                $remainingSeriesOrthancID=$visitObject->getSeriesOrthancID();
                
                if(count($remainingSeriesOrthancID)==0){
                    //Set study to deleted status
                    $seriesObject->studyDetailsObject->changeDeletionStatus(true);
                    //Set Visit upload status to Not Done and reset QC
                    $visitObject->resetQC();
                    $visitObject->changeVisitStateInvestigatorForm(Visit::LOCAL_FORM_DRAFT);
                    $visitObject->changeUploadStatus(Visit::NOT_DONE);
                }
                
                $changedArrayResult[]=$serieOrthancID;
                
            }else if($action=='reactivate'){
                if($role!=User::SUPERVISOR){
                    print("Reactivation not authorized");
                    return;
                }
                $seriesObject->changeDeletionStatus(false);
                $changedArrayResult[]=$serieOrthancID;
            }
            
        }
        
        //Log Activity
        $actionDetails['changed_series_orthancId']=$changedArrayResult;
        $actionDetails['action']=$action;
        $actionDetails['patient_code']=$visitObject->patientCode;
        $actionDetails['type_visit']=$visitObject->visitType;
        $actionDetails['Reason']=$reason;
        Tracker::logActivity($username, $role, $_SESSION['study'], $id_visit, "Change Serie", $actionDetails);
        $answer=true;
        
    } else{
        $answer=false;
    }

    echo(json_encode($answer));

} else {
    echo(json_encode(false));
}
