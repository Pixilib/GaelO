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
 * Access data for a study
 */

Class Study {
    
    private $linkpdo;
    private $study;

    //SK AJOUTER ETUDE ANCILLAIRE DE
    
    //public $formNeeded;
    //public $qcNeeded;
    //public $reviewNeeded;
    //public $daysLimitFromInclusion;
    
    
    public function __construct($study, $linkpdo){
        $this->linkpdo=$linkpdo;
        
        $connecter = $this->linkpdo->prepare('SELECT * FROM studies WHERE name=:study');
        $connecter->execute(array(
        		"study" => $study,
        ));
        $result = $connecter->fetch(PDO::FETCH_ASSOC);

        $this->study=$result['name'];
        
        //$this->qcNeeded=$result['qc'];
        //$this->formNeeded=$result['form'];
        //$this->reviewNeeded=$result['review'];
        //$this->daysLimitFromInclusion=$result['limit_days_visit_from_inclusion'];
        
        
    }
    
    /**
     * Return uploaded and non deleted visit Objects
     */
    public function getUploadedVisits(){
        
        $uploadedVisitQuery = $this->linkpdo->prepare('SELECT id_visit FROM visits WHERE study = :study
                                                    AND deleted=0
                                                    AND visits.upload_status="Done" ');
        
        $uploadedVisitQuery->execute(array('study' => $this->study));
        $uploadedVisitIds=$uploadedVisitQuery->fetchall(PDO::FETCH_COLUMN);
        
        $visitObjectArray=[];
        foreach ($uploadedVisitIds as $id_visit){
            $visitObjectArray[]=new Visit($id_visit, $this->linkpdo);
        }
        
        return $visitObjectArray;
        
    }
    
    public function getAwaitingUploadVisit(){
    	
    	$uploadedVisitQuery = $this->linkpdo->prepare("SELECT id_visit FROM visits WHERE study = :study
														AND deleted=0
														AND visits.upload_status ='Not Done'
														AND visits.status_done='Done' ");
    	
    	$uploadedVisitQuery->execute(array('study' => $this->study));
    	$uploadedVisitIds=$uploadedVisitQuery->fetchall(PDO::FETCH_COLUMN);
    	
    	$visitObjectArray=[];
    	foreach ($uploadedVisitIds as $id_visit){
    		$visitObjectArray[]=new Visit($id_visit, $this->linkpdo);
    	}
    	
    	return $visitObjectArray;
    	
    }
    
    /**
     * Get Visits awaiting review
     * Optionally visit awaiting review can be specific to an username
     * @param string $username
     * @return Visit[]
     */
    public function getAwaitingReviewVisit(string $username=null){
        
        //Query visit to analyze visit awaiting a review
        $idVisitsQuery = $this->linkpdo->prepare('SELECT id_visit FROM visits INNER JOIN visit_type ON (visits.visit_type=visit_type.name AND visits.study=visit_type.study)
                                      WHERE (visits.study = :study
                                      AND deleted=0
                                      AND review_available=1) ORDER BY visit_order ');
        
        $idVisitsQuery->execute(array('study' => $this->study));
        $visitList = $idVisitsQuery->fetchAll(PDO::FETCH_COLUMN);
        
        $visitObjectArray=[];
        
        foreach ($visitList as $visitId) {
            $visitObject= new Visit($visitId, $this->linkpdo);
            
            if(!empty($username)){
                if($visitObject->isAwaitingReviewForReviewerUser($username)) $visitObjectArray[]=$visitObject;
            }else{
                $visitObjectArray[]=$visitObject;
            }
           
        }
        
        return $visitObjectArray;
        
    }
    
    public function getVisitWithQCStatus($qcStatus){
        
        $visitQuery = $this->linkpdo->prepare("SELECT id_visit FROM visits WHERE study = :study
														AND deleted=0
                                                        AND state_quality_control=:qcStatus");
        
        $visitQuery->execute(array('study'=>$this->study, 'qcStatus' => $qcStatus));
        $visitIds=$visitQuery->fetchall(PDO::FETCH_COLUMN);
        
        $visitObjectArray=[];
        foreach ($visitIds as $id_visit){
            $visitObjectArray[]=new Visit($id_visit, $this->linkpdo);
        }
        
        return $visitObjectArray;
        
    }
    
    public function getVisitsMissingInvestigatorForm(){
        
        $visitQuery = $this->linkpdo->prepare(" SELECT id_visit FROM visits WHERE study = :study
                                                            AND deleted=0 
                                                            AND state_investigator_form !='Done' 
                                                            AND upload_status='Done'");
        
        $visitQuery->execute(array('study'=>$this->study));
        $visitIds=$visitQuery->fetchall(PDO::FETCH_COLUMN);
        
        $visitObjectArray=[];
        foreach ($visitIds as $id_visit){
            $visitObjectArray[]=new Visit($id_visit, $this->linkpdo);
        }
        
        return $visitObjectArray;
        
    }
    
    /**
     * Return studie's visit object
     */
    public function getCreatedVisits(bool $deleted=false){
        
        $uploadedVisitQuery = $this->linkpdo->prepare('SELECT id_visit FROM visits, visit_type WHERE visits.study = :study
                                                    AND visits.deleted=:deleted 
                                                    AND visit_type.name=visits.visit_type
                                                    AND visit_type.study=visits.study
                                                    ORDER BY patient_code, visit_type.visit_order');
        
        $uploadedVisitQuery->execute(array('study' => $this->study, 'deleted'=>intval($deleted)));
        $uploadedVisitIds=$uploadedVisitQuery->fetchAll(PDO::FETCH_COLUMN);
        
        $visitObjectArray=[];
        foreach ($uploadedVisitIds as $id_visit){
            $visitObjectArray[]=new Visit($id_visit, $this->linkpdo);
        }
        
        return $visitObjectArray;
        
    }
    
    
    public function getAllPossibleVisits(){
        $allVisitsType = $this->linkpdo->prepare('SELECT study, name FROM visit_type WHERE study = :study ORDER BY visit_order');
        $allVisitsType->execute(array('study' => $this->study));
        $allVisits=$allVisitsType->fetchall(PDO::FETCH_ASSOC);
        
        $visitTypeArray=[];
        foreach ($allVisits as $visit){
            $visitTypeArray[]=new Visit_Type($this->linkpdo, $visit['study'], $visit['name']);
        }
        
        return $visitTypeArray;
        
    }
    
    public function getAllPatientsInStudy(){
        $allPatientQuery = $this->linkpdo->prepare('SELECT code FROM patients WHERE study = :study');
        $allPatientQuery->execute(array('study' => $this->study));
        $allPatients=$allPatientQuery->fetchall(PDO::FETCH_COLUMN);
        
        $patientObjectArray=[];
        foreach ($allPatients as $patient){
            $patientObjectArray[]=new Patient($patient, $this->linkpdo);
        }
        
        return $patientObjectArray;
        
    }
    
    public function getDocumentation(String $role){
        if($role==User::SUPERVISOR){
            $documentationQuery = $this->linkpdo->prepare("SELECT id_documentation FROM documentation
                                                    WHERE study = :study");
            
        }else{
            $documentationQuery = $this->linkpdo->prepare("SELECT id_documentation FROM documentation
                                                    WHERE study = :study
                                                    AND ".$role."= 1 AND deleted=0");
            
        }
        
        $documentationQuery->execute(array('study' => $this->study));
        $documentationAnswers=$documentationQuery->fetchAll(PDO::FETCH_COLUMN);
        
        $documentationObjects=[];
        foreach ($documentationAnswers as $documentationId){
        	$documentationObjects[]=new Documentation($this->linkpdo, $documentationId);
        }
        return $documentationObjects;
    }
    
    /**
     * Return userObject array for all users having role in the study
     * @return User[]
     */
    public function getUsersWithRoleInStudy(){
        $req = $this->linkpdo->prepare('SELECT DISTINCT users.username FROM roles,users
                                  WHERE roles.username=users.username
                                  AND roles.study=:study');
        $req->execute(array('study' => $this->study));
        $answers=$req->fetchAll(PDO::FETCH_COLUMN);
        
        $usersObjects=[];
        foreach ($answers as $username){
            $usersObjects[]=new User($username, $this->linkpdo);
        }
        return $usersObjects;
        
    }
    

    public function getUsersByRoleInStudy(String $role){
        $req = $this->linkpdo->prepare('SELECT username FROM roles
									   WHERE study=:study AND name=:role ');
        $req->execute(array('study' => $this->study, 'role'=>$role));
        $answers=$req->fetchAll(PDO::FETCH_COLUMN);
        
        $usersObjects=[];
        foreach ($answers as $username){
            $usersObjects[]=new User($username, $this->linkpdo);
        }
        return $usersObjects;
        
    }
    
    public function getAllRolesByUsers(){
        $roles_query = $this->linkpdo->prepare('SELECT * FROM roles WHERE study=:study');
        $roles_query->execute(array('study'=>$this->study));
        $definedRoles=$roles_query->fetchall(PDO::FETCH_ASSOC);
        
        foreach ($definedRoles as $role){
            $rolesList[$role['username']][]=$role['name'];
        }
        return $rolesList;
    }

    /**
     * Return patient status for all patient in a study with date and status calculation for expected visit
     * @param string $study
     * @return string JSON
     */

    //SK A REVOIR A SPLITTER PAR VISITE ET PAR RAPPORT A LA DATE D INCLUSION
    //SK CETTE METHODE EST A REVOIR COMPLETEMENT
    public function getAllPatientsVisitsStatus(){
        
        //Get ordered list of possible visits in this study
        $allVisits=$this->getAllPossibleVisits($this->study);
        //Get patients list in this study
        $allPatients=$this->getAllPatientsInStudy($this->study);
        //Store actual time for comparison
        $todayDate = new DateTime ( date ( "Y-m-d" ) );
        
        $results=[];
        
        // For each patient determine it's visist status
        foreach($allPatients as $patient){
            
            $patientCenter=$patient->getPatientCenter();
            $createdVisits=$patient->getPatientsVisits();
            $patientRegistrationImmutableDate=new DateTimeImmutable($patient->patientRegistrationDate);
            
            //Add patient's common informations
            foreach($allVisits as $possibleVisit){
                $results[$possibleVisit->name][$patient->patientCode]['center']=$patientCenter->name;
                $results[$possibleVisit->name][$patient->patientCode]['country']=$patientCenter->countryName;
                $results[$possibleVisit->name][$patient->patientCode]['firstname']=$patient->patientFirstName;
                $results[$possibleVisit->name][$patient->patientCode]['lastname']=$patient->patientLastName;
                $results[$possibleVisit->name][$patient->patientCode]['birthdate']=$patient->patientBirthDate;
                $results[$possibleVisit->name][$patient->patientCode]['registration_date']=$patient->patientRegistrationDate;
            }
            
            //Make a map of all created visit
            $createdVisitMap=[];
            foreach ($createdVisits as $dataPatientVisit){
                //Acquisition date of created visit
            	$visitCharacteristics=$dataPatientVisit->getVisitCharacteristics();
            	$createdVisitMap[$visitCharacteristics->visitOrder]=$dataPatientVisit;
            }
            
            //Determine Visit Order missing
            $missingOrder=[];
            foreach ($allVisits as $possibleVisit){
                if(empty($createdVisitMap[$possibleVisit->visitOrder])){
                    $missingOrder[]=$possibleVisit->visitOrder;
                }
            }
            
            //Determine compliancy / status of created visits
            foreach($createdVisitMap as $visitOrder=>$dataPatientVisit ){

                $visitAcquisitionDate=$dataPatientVisit->getImmutableAcquisitionDate();
                
                //Search for the last created Visit
                $previousCreated=$visitOrder-1;
                while( empty($createdVisitMap[$previousCreated]) && $previousCreated>-1 ){
                    $previousCreated--;
                }
                
                $timeLimitAddition=0;
                for($i=$previousCreated+1 ; $i==$visitOrder ;$i++){
                    $timeLimitAddition+=$allVisits[$i]->limitNumberDays;
                    
                }

                //If first visit registration date is referece
                if($previousCreated=(-1)){
                    $daysincrease=$allVisits[0]->limitNumberDays;
                    $dateUpLimit=$patientRegistrationImmutableDate->modify($daysincrease.'day');
                    $dateDownLimit=$patientRegistrationImmutableDate->modify($this->daysLimitFromInclusion.'day');
                //Else calculate time from the last created and the current
                }else{   
                    $dateUpLimit=$createdVisitMap[$previousCreated]->getImmutableAcquisitionDate()->modify($timeLimitAddition.'day');
                    $dateDownLimit=$createdVisitMap[$previousCreated]->getImmutableAcquisitionDate();
                }
                
                //Status determination
                if($visitAcquisitionDate>=$dateDownLimit && $visitAcquisitionDate<=$dateUpLimit){
                    $results[$dataPatientVisit->visitType][$patient->patientCode]['compliancy']="Yes"; 
                }else{
                    $results[$dataPatientVisit->visitType][$patient->patientCode]['compliancy']="No";
                }
                
                $results[$dataPatientVisit->visitType][$patient->patientCode]['status']=Visit_Manager::DONE;
                $results[$dataPatientVisit->visitType][$patient->patientCode]['shouldBeDoneBefore']=$dateUpLimit->format('Y-m-d');
                $results[$dataPatientVisit->visitType][$patient->patientCode]['shouldBeDoneAfter']=$dateDownLimit->format('Y-m-d');
                $results[$dataPatientVisit->visitType][$patient->patientCode]['upload_status']=$dataPatientVisit->uploadStatus;
                $results[$dataPatientVisit->visitType][$patient->patientCode]['acquisition_date']=$visitAcquisitionDate->format('Y-m-d');
                $results[$dataPatientVisit->visitType][$patient->patientCode]['upload_date']=$dataPatientVisit->uploadDate;
                $results[$dataPatientVisit->visitType][$patient->patientCode]['id_visit']=$dataPatientVisit->id_visit;
                $results[$dataPatientVisit->visitType][$patient->patientCode]['state_investigator_form']=$dataPatientVisit->stateInvestigatorForm;
                $results[$dataPatientVisit->visitType][$patient->patientCode]['state_quality_control']=$dataPatientVisit->stateQualityControl;
                
                
            }
            
            //Determine the status of the missing visits
            foreach($missingOrder as $missingVisitNumber){
                
                //Determine visit number of the last created visit
                $lastAvailable=$missingVisitNumber;
                while( empty($createdVisitMap[$lastAvailable]) && $lastAvailable>=0){
                    $lastAvailable--;
                }

                //If 1st Visit missing reference date is the inclusion date
                if ($lastAvailable== -1) {
                    $lastCreatedVisit=$patientRegistrationImmutableDate;
                    $lastAvailable=0;
                    $shouldBeDoneAfter=$lastCreatedVisit->modify($this->daysLimitFromInclusion.' day');
                }
                else {
                    $lastCreatedVisit=$createdVisitMap[$lastAvailable]->getImmutableAcquisitionDate();
                    $shouldBeDoneAfter=$lastCreatedVisit;
                }
                
                $timeLimitAddition=0;
                //Determine the cummulative allowed time from the last available visit
                for($i=$lastAvailable ; $i<=$missingVisitNumber ; $i++){
                    $timeLimitAddition+=$allVisits[$i]->limitNumberDays;
                    
                }
                
                $dateUpLimit=$lastCreatedVisit->modify($timeLimitAddition.' day');
                //if patient withdraws visit might be not needed
                if($patient->patientWithdraw){
                    $withdrawDate=$patient->patientWithdrawDate;
                    if($lastCreatedVisit<=$withdrawDate && $dateUpLimit>$withdrawDate){
                        $results[$allVisits[$missingVisitNumber]->name][$patient->patientCode]['status']="Possibly Withdrawn";
                        
                    }else if($lastCreatedVisit>$withdrawDate){
                        $results[$allVisits[$missingVisitNumber]->name][$patient->patientCode]['status']="Visit Withdrawn";
                    }else{
                        $results[$allVisits[$missingVisitNumber]->name][$patient->patientCode]['status']=Visit_Manager::SHOULD_BE_DONE;    
                    }
                    
                }else{
                    if($todayDate>$dateUpLimit){
                        $results[$allVisits[$missingVisitNumber]->name][$patient->patientCode]['status']=Visit_Manager::SHOULD_BE_DONE;  
                    }else if($todayDate<=$dateUpLimit){
                        $results[$allVisits[$missingVisitNumber]->name][$patient->patientCode]['status']="Pending";
                        
                    }
                }
                
                $results[$allVisits[$missingVisitNumber]->name][$patient->patientCode]['compliancy']="";
                $results[$allVisits[$missingVisitNumber]->name][$patient->patientCode]['shouldBeDoneBefore']=$dateUpLimit->format('Y-m-d');
                $results[$allVisits[$missingVisitNumber]->name][$patient->patientCode]['shouldBeDoneAfter']=$shouldBeDoneAfter->format('Y-m-d');
                $results[$allVisits[$missingVisitNumber]->name][$patient->patientCode]['upload_status']="";
                $results[$allVisits[$missingVisitNumber]->name][$patient->patientCode]['acquisition_date']="";
                $results[$allVisits[$missingVisitNumber]->name][$patient->patientCode]['state_investigator_form']="";
                $results[$allVisits[$missingVisitNumber]->name][$patient->patientCode]['state_quality_control']="";
            }
            
            
        }
        
        return(json_encode($results));
        
    }

    public function getStatistics() {
        return new Statistics($this, $this->linkpdo);
    }
    
    public static function changeStudyActivation(String $study, bool $activated, PDO $linkpdo){
        $req = $linkpdo->prepare('UPDATE studies SET
    								active = :active
						        WHERE name = :study');
        $req->execute(array( 'study'=> $study, 'active'=>intval($activated)));
    }
    
    public function isOriginalOrthancNeverKnown($anonFromOrthancStudyId){
        
        $connecter = $this->linkpdo->prepare('SELECT Study_Orthanc_ID FROM orthanc_studies, visits WHERE orthanc_studies.id_visit=visits.id_visit AND Anon_From_Orthanc_ID=:Anon_From_Orthanc_ID AND visits.study=:study AND orthanc_studies.deleted=0 AND visits.deleted=0');
        $connecter->execute(array(
            "study" => $this->study,
            "Anon_From_Orthanc_ID"=>$anonFromOrthancStudyId
        ));
        $result = $connecter->fetchAll(PDO::FETCH_COLUMN);
        
        if(count($result)>0) return false; else return true;
        
    }
    
    public static function createStudy(string $studyName, PDO $linkpdo){
        
        //SK A AJOUTER IS ANCILLARY ET ANCILLARY OF
        
        $req = $linkpdo->prepare('INSERT INTO studies (name) VALUES(:studyName) ');
        
        $req->execute(array(
            'studyName' => $studyName
        ));
        
    }
    
}