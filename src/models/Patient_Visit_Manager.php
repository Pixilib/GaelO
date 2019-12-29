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
 * Determine Visit permissions for creation and status for upload manager
 */

class Patient_Visit_Manager {
    
    private $patientCode;
    private $linkpdo;
    private $study;
    private $patientObject;
    
    //Constants visit status available
    const DONE="Done";
    const NOT_DONE="Not Done";
    const SHOULD_BE_DONE="Should be done";
    const PENDING="Pending";
    const COMPLIANCY_YES="Yes";
    const COMPLIANCY_NO="No";
    const VISIT_WITHDRAWN="Visit Withdrawn";
    const VISIT_POSSIBLY_WITHDRAWN="Possibly Withdrawn";
    const OPTIONAL_VISIT="OPTIONAL_VISIT";

    public function __construct(Patient $patientObject){
        $this->linkpdo=$patientObject->linkpdo;
        $this->patientCode=$patientObject->patientCode;
        $this->patientObject=$patientObject;
        $this->study=$this->patientObject->patientStudy;
    }
    

    /**
     * Return created visits of a given patient
     * @param bool $deletedVisits
     * @return Visit[]
     */
    //SK ICI IL FAUDRA AJOUTER LE VISIT GROUPE OU VIA LE CONSTRUCTEUR
    public function getCreatedPatientsVisits(bool $deletedVisits=false){
    	
    	$visitQuery = $this->linkpdo->prepare('SELECT id_visit FROM visits
													INNER JOIN visit_type ON (visit_type.name=visits.visit_type AND visit_type.study=visits.study)
                                          			WHERE patient_code = :patientCode
													AND visits.deleted=:deleted
													ORDER BY visit_type.visit_order');
    	
    	
    	$visitQuery->execute(array('patientCode' => $this->patientCode, 'deleted'=>$deletedVisits));
    	
    	$visitsResults = $visitQuery->fetchAll(PDO::FETCH_COLUMN);
    	
    	$visitsObjectArray=[];
    	foreach ($visitsResults as $idVisit){
    		$visitsObjectArray[]=new Visit($idVisit, $this->linkpdo);
    	}
    	
    	return $visitsObjectArray;
    	
    }

    /**
     * Return array of non created visit name for current patient
     */
    public function getNotCreatedVisitName(){

        $allPossibleVisits=$this->patientObject->getPatientStudy()->getAllPossibleVisitTypes();
        $createdVisits=$this->getCreatedPatientsVisits();

        $createdVisitName=array_map(function (Visit_Type $visitType){return $visitType->name;},  $createdVisits);
        $possibleVisitName=array_map(function (Visit_Type $visitType){return $visitType->name;},  $allPossibleVisits);

        $missingVisitName= array_diff($possibleVisitName, $createdVisitName);

        return $missingVisitName;

    }
    
    /**
     * Retrun non created visits array for the current patient before a current visit order
     * This methods find a deleted visit when the next visit is already created
     */
    /*
    private function getNotCreatedVisitBefore($visitOrderMax){


    	
    	$existingVisits = $this->linkpdo->prepare ( 'SELECT name
														FROM visit_type 
														WHERE visit_type.study=(SELECT patients.study FROM patients WHERE patients.code= :patient)
															AND name NOT IN (SELECT visits.visit_type FROM visits WHERE visits.patient_code=:patient 
															AND visits.deleted=0) AND visit_order < :visitOrder' );
    	
    	$existingVisits->execute ( array('patient' => $this->patientCode,
    									'visitOrder'=>$visitOrderMax
    	) );
    	$missingVisits=$existingVisits->fetchAll(PDO::FETCH_COLUMN);
    	
    	return $missingVisits;
    	
    }
    */
    
    //Retourne : la visite en attente de creation en 1er => celle apres la derniere crée et n+1 si visite optionnelle
    //et les visites suprimées
    //SK A Tester
    public function getAvailableVisitsToCreate(){

        // if withdraw disallow visit creation
        if ($this->patientObject->patientWithdraw) {
            $availableVisitName[] = Patient::PATIENT_WITHDRAW;
            return $availableVisitName;
        }

        $allPossibleVisits=$this->patientObject->getPatientStudy()->getAllPossibleVisitTypes();
        $createdVisits=$this->getCreatedPatientsVisits();

        $createdVisitOrder=array_map(function (Visit_Type $visitType){return $visitType->visitOrder;},  $createdVisits);

        $lastCreatedVisitOrder=max($createdVisitOrder);

        $availableVisitName=[];

        foreach($allPossibleVisits as $possibleVisit){

            if($possibleVisit->visitOrder < $lastCreatedVisitOrder){
                $availableVisitName[]=$possibleVisit->name;
            }else{
                if($possibleVisit->optionalVisit){
                    //If optional add optional visit and look for the next order
                    $availableVisitName[]=$possibleVisit->name;
                    $lastCreatedVisitOrder++;
                } else if($possibleVisit->visitOrder == ($lastCreatedVisitOrder+1) ){
                    $availableVisitName[]=$possibleVisit->name;
                    break;
                }
            }

        }

        //Reverse to sort for the more advanced visit to create
        $availableVisitName=array_reverse($availableVisitName);

        if(empty($availableVisitName)) {
            $availableVisitName[] = "Error - Please check that the study contains possible visits";
        }

        return $availableVisitName;

    }
    
    /**
     * Return the visit able to be created, the n+1 visits + eventual previously deleted visits
     * @return string[]
     */
    //SK CETTE METHODE EST A REVOIR
    //PROBABLEMENT A SPLITER
    /*
    public function getNextVisitToCreate(){
        
        //  List already existing visits for this patient
        $existingVisits = $this->linkpdo->prepare ( 'SELECT MAX(visit_order) AS maxOrderExisting, patients.study FROM visits, patients, visit_type
										      WHERE visits.patient_code = patients.code
										      AND patients.code = :patient
										      AND visits.visit_type = visit_type.name
											  AND visit_type.study =patients.study 
                                              AND visits.deleted=0' );
        
        $existingVisits->execute ( array('patient' => $this->patientCode) );
        
        $existingResults = $existingVisits->fetch(PDO::FETCH_ASSOC);
       
        //List all visits possible in the study
        $studyObject=new Study($this->study,$this->linkpdo);
        $dataAllVisits=$studyObject->getAllPossibleVisitTypes();
        
        foreach ( $dataAllVisits as $value ) {
            $orderAllVisits [] = $value->visitOrder;
        }
        
        //Add  eventual deleted visits before the last created
        $deletedVisit=$this->getNotCreatedVisitBefore($existingResults['maxOrderExisting']);
        
        $typeVisiteDispo=[];
        //Determine what is the next visit to be allowed to create
        if (isset ( $existingResults['maxOrderExisting'] ) && isset ( $orderAllVisits )) {
        	if ($existingResults['maxOrderExisting'] < max( $orderAllVisits ) ) {
                $indexDispo =$existingResults['maxOrderExisting'] + 1;
                $typeVisiteDispo[] = $dataAllVisits[$indexDispo]->name;
                
                if(!empty($deletedVisit)){
                	$typeVisiteDispo=array_merge($typeVisiteDispo, $this->getNotCreatedVisitBefore($existingResults['maxOrderExisting']));	
                }
                //Max index reached no further visit to be created
        	}else if(!empty($deletedVisit)){
        		$typeVisiteDispo=$deletedVisit;
            	
            }else {
                $typeVisiteDispo[] = 'Not Possible';
            }
        //If not visit created, get the first one in visit list
        } else if (isset ( $orderAllVisits )) {
            $typeVisiteDispo[] = $dataAllVisits[0]->name;
        } else {
            $typeVisiteDispo[] = "Error - Please check that the study contains possible visits";
        }
        
        // if withdraw disallow visit creation
        if ($this->patientObject->patientWithdraw) {
            $typeVisiteDispo[] = "withdraw";
        }
        
        return $typeVisiteDispo;
        
    }
    */
    /**
     * Return if there are still visits that are awaiting to be created for this patient
     * @return boolean
     */
    public function isMissingVisit(){
        if(sizeof($this->getNotCreatedVisitName()) >0) return true;
        else return false;
    }

    /**
     * Determine Visit Status of a patient
     * Theorical date are calculated from registration date and compared to
     * acquisition date if visit created or actual date for non created visit
     */
    public function determineVisitStatus(String $visitName){

        $registrationDate=$this->patientObject->getImmutableRegistrationDate();

        $visitType=new Visit_Type($this->linkpdo, $this->study, $visitName);

        $dateDownLimit=$registrationDate->modify($visitType->limitLowDays.'day');
        $dateUpLimit=$registrationDate->modify($visitType->limitUpDays.'day');

        $visitAnswer['status']=null;
        $visitAnswer['compliancy']=null;
        $visitAnswer['shouldBeDoneBefore']=$dateUpLimit->format('Y-m-d');
        $visitAnswer['shouldBeDoneAfter']=$dateDownLimit->format('Y-m-d');
        $visitAnswer['state_investigator_form']=null;
        $visitAnswer['state_quality_control']=null;
        $visitAnswer['acquisition_date']=null;
        $visitAnswer['upload_date']=null;
        $visitAnswer['upload_status']=null;
        $visitAnswer['id_visit']=null;

        try{
            //Visit Created check compliancy
            $visitObject = Visit::getVisitbyPatientAndVisitName($this->patientCode, $visitName, $this->linkpdo );
            $visitAnswer['state_investigator_form']=$visitObject->stateInvestigatorForm;
            $visitAnswer['state_quality_control']=$visitObject->stateQualityControl;
            $visitAnswer['acquisition_date']=$visitObject->acquisitionDate;
            $visitAnswer['upload_date']=$visitObject->uploadDate;
            $visitAnswer['upload_status']=$visitObject->uploadStatus;
            $visitAnswer['id_visit']=$visitObject->id_visit;
            $testedDate=$visitObject->acquisitionDate;
            $visitAnswer['status']=Patient_Visit_Manager::DONE;

            if($testedDate>=$dateDownLimit && $testedDate<=$dateDownLimit){
                $visitAnswer['compliancy']=Patient_Visit_Manager::COMPLIANCY_YES;
            }else{
                $visitAnswer['compliancy']=Patient_Visit_Manager::COMPLIANCY_NO;
            }

        }catch (Exception $e){
             //Visit Not Created
             //If optional visit no status determination
            if($visitType->optionalVisit){
                $visitAnswer['status']=Patient_Visit_Manager::OPTIONAL_VISIT;
            }else{
                //Compare actual time with theorical date to determine status
                $testedDate = new DateTime ( date ( "Y-m-d" ) );
                if($testedDate<=$dateUpLimit){
                    $visitAnswer['status']=Patient_Visit_Manager::PENDING;
                }else {
                    $visitAnswer['status']=Patient_Visit_Manager::SHOULD_BE_DONE;
                }

            }

        }

        //Take account of possible withdrawal if not created
        if($this->patientObject->patientWithdraw &&  $visitAnswer['acquisition_date']==null){
            if( $this->patientObject->patientWithdrawDate < $dateDownLimit ){
                $visitAnswer['status']=Patient_Visit_Manager::VISIT_WITHDRAWN;
            }else if( $this->patientObject->patientWithdrawDate > $dateDownLimit ){
                $visitAnswer['status']=Patient_Visit_Manager::VISIT_POSSIBLY_WITHDRAWN;
            }
        }

        return $visitAnswer;

    }

    /**
     * Return visits of this patient available for review
     */
    public function getAwaitingReviewVisits(){

        $createdVisits=$this->getCreatedPatientsVisits();

        $availableVisitsForReview=[];

        foreach($createdVisits as $visit){
            if($visit->reviewAvailable){
                $availableVisitsForReview[]=$visit;
            }
        }

        return $availableVisitsForReview;
    }

    public function isHavingAwaitingReviewVisit(){
        $awaitingReviews = $this->getAwaitingReviewVisits();
        return ( ! empty($awaitingReviews) );
    }
    
   
  
}