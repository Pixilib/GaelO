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
 * Build JSON for JSTree with patient's / visit's data
 *
 */

class Tree {
    
  private $json;
  private $role;
  private $username;
  private $study;
  private $studyObject;
  private $linkpdo;

  //SK AJOUTER LE NIVEAU VISIT GROUP SI >1GROUPE
 
  public function __construct(string $role, string $username, string $study, PDO $linkpdo){
      $this->linkpdo=$linkpdo;
      $this->role=$role;
      $this->username=$username;
      $this->study=$study;
      $this->studyObject=new Study($study, $linkpdo);
      
  }

  private function getStudyManagerArray(){

        $visitGroups=$this->studyObject->getAllPossibleVisitGroups();

        $visitManagerArray=[];

        foreach($visitGroups as $visitGroup){
            $visitManagerArray[]=$this->studyObject->getStudySpecificGroupManager($visitGroup->groupModality);
        }
  }

  //SK ENCORE A GLOBALISER AVEC MULTIPLE MODALITE
  public function buildTree(){

    $studyGroupManager=$this->studyObject->getStudySpecificGroupManager(Visit_Group::GROUP_MODALITY_PET);
    $treeArray=$this->prepareTree($studyGroupManager);

    return $treeArray;

  }

  /**
   * Determine class value of Investigator and Controller visit item
   * to make specific color decoration depending on status of visit
   */
  private function determineClassOfVisit(Visit $visitObject) : String {

    if($this->role==User::INVESTIGATOR){

      //Add upload status / user form in class 
      if( $visitObject->statusDone==Visit::DONE && $visitObject->uploadStatus == Visit::NOT_DONE && $visitObject->stateInvestigatorForm != Visit::DONE ){
        $class="NotBoth";
      }
      else if( $visitObject->statusDone==Visit::DONE && $visitObject->stateInvestigatorForm !=  Visit::DONE ){
          $class="NotForm";
      }
      else if( $visitObject->statusDone==Visit::DONE && $visitObject->uploadStatus ==  Visit::NOT_DONE ){
          $class="NotUpload";
      }
      else{
          $class="OK";
      }

    }else if($this->role==User::CONTROLLER){
      if($visitObject->stateQualityControl==Visit::QC_ACCEPTED || $visitObject->stateQualityControl==Visit::QC_REFUSED){
        $class="OK";  
      }else if ($visitObject->stateQualityControl==Visit::QC_NOT_DONE || $$visitObject->stateQualityControl==Visit::QC_WAIT_DEFINITVE_CONCLUSION){
        $class="NotBoth";  
      }

    }

    return $class;

  }

  private function visitObjectToTreeObject(Visit $visitObject){

    $jsonVisitLevel['id'] = $visitObject->id_visit;
    $jsonVisitLevel['parent'] = $visitObject->patientCode;
    $jsonVisitLevel['icon'] = '/assets/images/report-icon.png';
    $jsonVisitLevel['text'] = $visitObject->visitType;

    if( $this->role==User::INVESTIGATOR ||  $this->role == User::CONTROLLER){
      //NB SI BESOIN ON PEUT AJOUTER UN CUSTOM ATRRIBUT A LA PLACE DE class
      $attr['class'] = $this->determineClassOfVisit($visitObject);
      $jsonVisitLevel['li_attr'] =$attr;
    }
    
    return $jsonVisitLevel;

  }

  private function patientObjectToTreeObject(String $patientCode){

    $jsonPatientLevel['id'] = $patientCode;
    $jsonPatientLevel['parent'] = '#';
    $jsonPatientLevel['icon'] = '/assets/images/person-icon.png';
    $jsonPatientLevel['text'] = $patientCode;

    return $jsonPatientLevel;

  }

  /**
   * Return JSON for JSTree according to role  (patient + Visit)
   * @return array
   */
  public function prepareTree(Study_Visit_Manager $studyVisitManager){

    $resultTreeArray=[];
    
    if($this->role == User::INVESTIGATOR){
      //retrieve from DB the patient's list of the requested study and included in user's center or affiliated centers

      $patientObjectArray=$studyVisitManager->getPatientLinkedToUserCenters($this->username);
      
      foreach($patientObjectArray as $patient){
    
        $resultTreeArray[] =$this->patientObjectToTreeObject($patient->patientCode);

        $patientVisitManager=$patient->getVisitManager($studyVisitManager->getVisitGroupObject());
        $createdPatientVisits=$patientVisitManager->getCreatedPatientsVisits();
        
        foreach($createdPatientVisits as $createdVisit){
            $resultTreeArray[] = $this->visitObjectToTreeObject($createdVisit);
        }

      }

    } else if($this->role == User::CONTROLLER){
      $controllerVisitsArray = $studyVisitManager->getVisitForControllerAction();

      $patientsArray=[];

      foreach ($controllerVisitsArray as $visitObject) {
          
          //Check if visit comes from a new patient
          if(  ! in_array($visitObject->patientCode, $patientsArray) ){
              //create a patient entry
              $resultTreeArray[] =$this->patientObjectToTreeObject($visitObject->patientCode);
          }
          
          $resultTreeArray[] = $this->visitObjectToTreeObject($visitObject);
      }

    } else if($this->role == User::MONITOR){

        $createdVisitArray=$patient->getVisitManager($studyVisitManager->getVisitGroupObject())->getCreatedVisits();

        $patientsArray=[];

        foreach ($createdVisitArray as $visitObject) {
            
            //Check if visit comes from a new patient
            if(  ! in_array($visitObject->patientCode, $patientsArray) ){
                //create a patient entry
                $resultTreeArray[] =$this->patientObjectToTreeObject($visitObject->patientCode);
                
            }
            
            $resultTreeArray[] = $this->visitObjectToTreeObject($visitObject);
        }

    } else if($this->role == User::REVIEWER){


        $visitObjectList = $this->studyObject->getStudySpecificGroupManager(Visit_Group::GROUP_MODALITY_PET)->getAwaitingReviewVisit($this->username);
        
        $patientsArray=[];
        
        foreach ($visitObjectList as $visitObject) {
            
            //Check if visit comes from a new patient
            if(  ! in_array($visitObject->patientCode, $patientsArray) ){

                //create a patient entry
                $resultTreeArray[] =$this->patientObjectToTreeObject($visitObject->patientCode);

                //Add all created visits of this patient to allow access to patient history
                $patientObject=new Patient($visitObject->patientCode, $this->linkpdo);
                $visitGroupObject=$patientObject->getPatientStudy()->getSpecificGroup(Visit_Group::GROUP_MODALITY_PET);
                $createdVisitsOject=$patientObject->getVisitManager($visitGroupObject)->getCreatedPatientsVisits();

                foreach($createdVisitsOject as $visitObject){
                  $resultTreeArray[] =$this->visitObjectToTreeObject;
                }
                
            }
            
        }
        
    }
    
    return  $resultTreeArray;
  }
  
}