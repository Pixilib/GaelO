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

class Visit_Manager {
    
    private $patientNum;
    private $withdraw;
    private $linkpdo;
    private $study;
    
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

    public function __construct(string $patientNum, PDO $linkpdo){
        $this->linkpdo=$linkpdo;
        $this->patientNum=$patientNum;
        
        $patientQuery = $this->linkpdo->prepare ( 'SELECT study, withdraw  FROM patients
										      WHERE patients.code = :patient' );
        
        $patientQuery->execute ( array('patient' => $this->patientNum) );
        $patientResults = $patientQuery->fetch(PDO::FETCH_ASSOC);
        $this->study=$patientResults['study'];
        $this->withdraw=$patientResults['withdraw'];
        
        
    }
    
    /**
     * Retrun non created visits array for the current patient before a current visit order
     * This methods find a deleted visit when the next visit is already created
     */
    private function getNotCreatedVisitBefore($visitOrderMax){
    	
    	$existingVisits = $this->linkpdo->prepare ( 'SELECT name
														FROM visit_type 
														WHERE visit_type.study=(SELECT patients.study FROM patients WHERE patients.code= :patient)
															AND name NOT IN (SELECT visits.visit_type FROM visits WHERE visits.patient_code=:patient 
															AND visits.deleted=0) AND visit_order < :visitOrder' );
    	
    	$existingVisits->execute ( array('patient' => $this->patientNum,
    									'visitOrder'=>$visitOrderMax
    	) );
    	$missingVisits=$existingVisits->fetchAll(PDO::FETCH_COLUMN);
    	
    	return $missingVisits;
    	
    }
    
    /**
     * Return the visit able to be created, the n+1 visits + eventual previously deleted visits
     * @return string[]
     */
    public function getNextVisitToCreate(){
        
        //  List already existing visits for this patient
        $existingVisits = $this->linkpdo->prepare ( 'SELECT MAX(visit_order) AS maxOrderExisting, patients.study FROM visits, patients, visit_type
										      WHERE visits.patient_code = patients.code
										      AND patients.code = :patient
										      AND visits.visit_type = visit_type.name
											  AND visit_type.study =patients.study 
                                              AND visits.deleted=0' );
        
        $existingVisits->execute ( array('patient' => $this->patientNum) );
        
        $existingResults = $existingVisits->fetch(PDO::FETCH_ASSOC);
       
        //List all visits possible in the study
        $studyObject=new Study($this->study,$this->linkpdo);
        $dataAllVisits=$studyObject->getAllPossibleVisits();
        
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
        if ($this->withdraw) {
            $typeVisiteDispo[] = "withdraw";
        }
        
        return $typeVisiteDispo;
        
    }
    
    /**
     * Return if there are still visits that are awaiting to be created for this patient
     * @return boolean
     */
    public function isMissingVisit(){
    	
    	$queryVisits = $this->linkpdo->prepare ( 'SELECT name
														FROM visit_type
														WHERE visit_type.study=(SELECT patients.study FROM patients WHERE patients.code= :patient)
															AND name NOT IN (SELECT visits.visit_type FROM visits WHERE visits.patient_code=:patient
															AND visits.deleted=0)');
    	
    	$queryVisits->execute ( array('patient' => $this->patientNum) );
    	$missingVisits=$queryVisits->fetchAll(PDO::FETCH_COLUMN);
        
    	if(!empty($missingVisits)) return true;
        else return false;
    }

    /**
     * Determine Visit Status of a patient
     * Theorical date are calculated from registration date and compared to
     * acquisition date if visit created or actual date for non created visit
     */
    public static function determineVisitStatus(Patient $patientObject, String $visitName, PDO $linkpdo){

        $registrationDate=$patientObject->getImmutableRegistrationDate();

        $visitType=new Visit_Type($linkpdo, $patientObject->patientStudy, $visitName);

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
            $visitObject = Visit::getVisitbyPatientAndVisitName($patientObject->patientCode, $visitName, $linkpdo );
            $visitAnswer['state_investigator_form']=$visitObject->stateInvestigatorForm;
            $visitAnswer['state_quality_control']=$visitObject->stateQualityControl;
            $visitAnswer['acquisition_date']=$visitObject->acquisitionDate;
            $visitAnswer['upload_date']=$visitObject->uploadDate;
            $visitAnswer['upload_status']=$visitObject->uploadStatus;
            $visitAnswer['id_visit']=$visitObject->id_visit;
            $testedDate=$visitObject->acquisitionDate;
            $visitAnswer['status']=Visit_Manager::DONE;

            if($testedDate>=$dateDownLimit && $testedDate<=$dateDownLimit){
                $visitAnswer['compliancy']=Visit_Manager::COMPLIANCY_YES;
            }else{
                $visitAnswer['compliancy']=Visit_Manager::COMPLIANCY_NO;
            }

        }catch (Exception $e){
             //Visit Not Created
             //If optional visit no status determination
            if($visitType->optionalVisit){
                $visitAnswer['status']=Visit_Manager::OPTIONAL_VISIT;
            }else{
                //Compare actual time with theorical date to determine status
                $testedDate = new DateTime ( date ( "Y-m-d" ) );
                if($testedDate<=$dateUpLimit){
                    $visitAnswer['status']=Visit_Manager::PENDING;
                }else {
                    $visitAnswer['status']=Visit_Manager::SHOULD_BE_DONE;
                }

            }

        }

        //Take account of possible withdrawal if not created
        if($patientObject->patientWithdraw &&  $visitAnswer['acquisition_date']==null){
            if( $patientObject->patientWithdrawDate < $dateDownLimit ){
                $visitAnswer['status']=Visit_Manager::VISIT_WITHDRAWN;
            }else if( $patientObject->patientWithdrawDate > $dateDownLimit ){
                $visitAnswer['status']=Visit_Manager::VISIT_POSSIBLY_WITHDRAWN;
            }
        }

        return $visitAnswer;

    }
    
   
  
}