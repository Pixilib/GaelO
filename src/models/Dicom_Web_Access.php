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
 * Check access for each call of the DICOMWeb Protocol (for OHIF / Viewer integration)
 * @author salim
 *
 */
class Dicom_Web_Access {

    private $isStudyRequested;
    private $isSerieRequested;
    private $requestedURI;
    private $userObject;
    private $userRole;
    private $linkpdo;
    
    public function __construct(string $requestedURI, User $userObject, string $userRole, PDO $linkpdo){
        $this->requestedURI=$requestedURI;
        $this->userObject=$userObject;
        $this->userRole=$userRole;
        $this->linkpdo=$linkpdo;
        
        if(strpos($requestedURI, "/series/")!== false) $this->isSerieRequested=true;
        else if(strpos($requestedURI, "/studies/") !==false) $this->isStudyRequested=true;
        
    }
    
    /**
     * Output the decision for access allowance
     * @return boolean
     */
    public function getDecision(){
        //Get related visit ID of the called ressource
        $id_visit=$this->getRelatedVisitID($this->getUID());
        
        //Return test of acess allowance
        return $this->isAccessAllowedForUser($id_visit);
    }
    
    /**
     * Isolate the called Study or Series Instance UID 
     * @return string
     */
    private function getUID(){
        if($this->isSerieRequested) $level="series";
        else if($this->isStudyRequested) $level="studies";
        $studySubString=strstr($this->requestedURI, "/".$level."/");
        $studySubString=str_replace("/".$level."/", "", $studySubString);
        $endStudyUIDPosition=strpos($studySubString, "/");
        $studyUID=substr($studySubString, 0, $endStudyUIDPosition);
        return $studyUID;
    }
    
    /**
     * Check if called ressource is allowed for current user
     * @param string $uid
     * @return string
     */
    private function getRelatedVisitID(string $uid){
       
        if($this->isSerieRequested) {
            $seriesObject=Series_Details::getSerieObjectByUID($uid, $this->linkpdo);
            $studyObject=$seriesObject->studyDetailsObject;
            
        } else if($this->isStudyRequested) {
            $studyObject=Study_Details::getStudyObjectByUID($uid, $this->linkpdo);
        }
        
        return $studyObject->idVisit;
        
    }
    
    /**
     * Check that visit is granter for the calling user (still awaiting review or still awaiting QC)
     * @param string $id_visit
     * @return boolean
     */
    private function isAccessAllowedForUser(string $id_visit){
        
        $visitObject=new Visit($id_visit, $this->linkpdo);
        
        //Check Visit Availability of the calling user
        if($this->userRole == User::REVIEWER || ($this->userRole == User::INVESTIGATOR && $visitObject->uploadStatus==Visit::DONE) ) {
            //Check that visit is in patient that is still awaiting for some reviews
            $visitCheck=$this->userObject->isVisitAllowed($id_visit, $this->userRole);
        }else if($this->userRole == User::CONTROLLER){
            //Check that QC status still require an action from Controller
            if(in_array($visitObject->stateQualityControl, array(Visit::QC_WAIT_DEFINITVE_CONCLUSION, Visit::QC_NOT_DONE)) ){
                $visitCheck=$this->userObject->isVisitAllowed($id_visit, $this->userRole);
            }
        }else{
            //Other roles can't have access to images
            $visitCheck=false;
        }
        
        return $visitCheck;
        
    }

}