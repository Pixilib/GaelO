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
	public $study;
	public $patientCodePrefix;
    
	public function __construct(String $study, PDO $linkpdo){

		$this->linkpdo=$linkpdo;
		$connecter = $this->linkpdo->prepare('SELECT * FROM studies WHERE name=:study');
		$connecter->execute(array(
				"study" => $study,
		));
		$result = $connecter->fetch(PDO::FETCH_ASSOC);

		$this->study=$result['name'];
		$this->patientCodePrefix = $result['patient_code_prefix'];
        
        
	}

	public function getPatientsLinkedToUserCenters($username)
	{
		$patients = $this->linkpdo->prepare(' SELECT patients.code
            FROM   patients
            WHERE  patients.center IN (SELECT affiliated_centers.center
                FROM   affiliated_centers
                WHERE  affiliated_centers.username = :username
                UNION
                SELECT users.center
                FROM   users
                WHERE  users.username = :username)
                AND study = :study
                GROUP  BY patients.code');

		$patients->execute(array(
			'username' => $username,
			'study' => $this->study
		));

		$patientsCodes = $patients->fetchAll(PDO::FETCH_COLUMN);

		$patientObjectsArray = [];

		foreach ($patientsCodes as $patientCode) {
			$patientObjectsArray[] = new Patient($patientCode, $this->linkpdo);
		}

		return $patientObjectsArray;
	}

	public function getAllCreatedVisits(bool $deleted=false){

		$possibleStudyGroups=$this->getAllPossibleVisitGroups();

		$visitsObjectArray = [];

		foreach($possibleStudyGroups as $studyGroup){
			$createdVisits=$studyGroup->getStudyVisitManager()->getCreatedVisits($deleted);
			array_push($visitsObjectArray, ...$createdVisits);
		}

		return $visitsObjectArray;

	}

	public function getAllAwaitingUploadImagingVisit(){

		$possibleStudyGroups=$this->getAllPossibleVisitGroups();
		$visitsObjectArray = [];

		foreach($possibleStudyGroups as $studyGroup){
			if(in_array($studyGroup->groupModality, array(Visit_Group::GROUP_MODALITY_CT, Visit_Group::GROUP_MODALITY_MR, Visit_Group::GROUP_MODALITY_PET)) ){
				$awaitingUploadVisits=$studyGroup->getStudyVisitManager()->getAwaitingUploadVisit();
				array_push($visitsObjectArray, ...$awaitingUploadVisits);
			}
            
            
		}
        
		return $visitsObjectArray;

	}

	public function getAllAwaitingReviewImagingVisit($username = null){

		$possibleStudyGroups=$this->getAllPossibleVisitGroups();
		$visitsObjectArray = [];

		foreach($possibleStudyGroups as $studyGroup){
			if(in_array($studyGroup->groupModality, array(Visit_Group::GROUP_MODALITY_CT, Visit_Group::GROUP_MODALITY_MR, Visit_Group::GROUP_MODALITY_PET)) ){
				$awaitingReviewVisits=$studyGroup->getStudyVisitManager()->getAwaitingReviewVisit($username);
				array_push($visitsObjectArray, ...$awaitingReviewVisits);
			}
            
            
		}
        
		return $visitsObjectArray;

	}

	public function getAllUploadedImagingVisits(){

		$possibleStudyGroups=$this->getAllPossibleVisitGroups();
		$visitsObjectArray = [];

		foreach($possibleStudyGroups as $studyGroup){
			if(in_array($studyGroup->groupModality, array(Visit_Group::GROUP_MODALITY_CT, Visit_Group::GROUP_MODALITY_MR, Visit_Group::GROUP_MODALITY_PET)) ){
				$uploadedVisits=$studyGroup->getStudyVisitManager()->getUploadedVisits();
				array_push($visitsObjectArray, ...$uploadedVisits);
			}
            
            
		}
        
		return $visitsObjectArray;

	}

	public function isHavingAwaitingReviewImagingVisit($username=null){
		$awaitingVisits=$this->getAllAwaitingReviewImagingVisit($username);
		$havingAwaitingReview= (sizeof($awaitingVisits) > 0);
		return $havingAwaitingReview;
	}



	public function getAllPossibleVisitGroups(){

		$allGroupsType = $this->linkpdo->prepare('SELECT id FROM visit_group WHERE study = :study');
		$allGroupsType->execute(array('study' => $this->study));
		$allGroupsIds=$allGroupsType->fetchall(PDO::FETCH_COLUMN);
        
		$visitGroupArray=[];
		foreach ($allGroupsIds as $groupId){
			$visitGroupArray[]=new Visit_Group($this->linkpdo, $groupId);
		}
        
		return $visitGroupArray;

	}

	public function getSpecificGroup(String $groupModality) : Visit_Group {

		$groupQuery = $this->linkpdo->prepare('SELECT id FROM visit_group WHERE study = :study AND group_modality=:groupModality');
		$groupQuery->execute(array('study' => $this->study, 'groupModality'=> $groupModality));
		$groupId=$groupQuery->fetch(PDO::FETCH_COLUMN);
        
		return new Visit_Group($this->linkpdo, $groupId);

	}

	public function getStudySpecificGroupManager(String $groupModality) : Group_Visit_Manager {

		$visitGroup=$this->getSpecificGroup($groupModality);
        
		return new Group_Visit_Manager($this, $visitGroup, $this->linkpdo);

	}

	public function getReviewManager() : Study_Review_Manager {
		return new Study_Review_Manager($this);
	}

	public function getExportStudyData() : Export_Study_Data{
		return new Export_Study_Data($this);
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

	public function getStatistics(String $modality) {
		return new Statistics($this, $modality);
	}
    
	public function changeStudyActivation(bool $activated){
		$req = $this->linkpdo->prepare('UPDATE studies SET
    								active = :active
						        WHERE name = :study');
		$req->execute(array( 'study'=> $this->study, 'active'=>intval($activated)));
	}
    
	public function isOriginalOrthancNeverKnown($anonFromOrthancStudyId){

		$connecter = $this->linkpdo->prepare('SELECT Study_Orthanc_ID FROM visits 
                                                INNER JOIN visit_type ON 
                                                (visit_type.id=visits.visit_type_id 
                                                AND visit_type.group_id IN (SELECT id FROM visit_group WHERE study = :study))
                                                INNER JOIN orthanc_studies ON (orthanc_studies.id_visit=visits.id_visit) 
                                            AND orthanc_studies.Anon_From_Orthanc_ID=:Anon_From_Orthanc_ID 
                                            AND orthanc_studies.deleted=0 
                                            AND visits.deleted=0'
											);
		$connecter->execute(array(
			"study" => $this->study,
			"Anon_From_Orthanc_ID"=>$anonFromOrthancStudyId
		));
		$result = $connecter->fetchAll(PDO::FETCH_COLUMN);
        
		if(count($result)>0) return false; else return true;
        
	}
    
	public static function createStudy(string $studyName, $patientCodePrefix, PDO $linkpdo){
        
		$req = $linkpdo->prepare('INSERT INTO studies (name, patient_code_prefix) VALUES(:studyName, :patientCodePrefix) ');
        
		$req->execute(array(
			'studyName' => $studyName,
			'patientCodePrefix' => $patientCodePrefix
		));
        
	}
    
}