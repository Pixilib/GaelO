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

    //SK AJOUTER "ETUDE ANCILLAIRE DE" OU VIA HERITAGE ?!?
    
    public function __construct(String $study, PDO $linkpdo){

        $this->linkpdo=$linkpdo;
        $connecter = $this->linkpdo->prepare('SELECT * FROM studies WHERE name=:study');
        $connecter->execute(array(
        		"study" => $study,
        ));
        $result = $connecter->fetch(PDO::FETCH_ASSOC);

        $this->study=$result['name'];
        
        
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

    public function getSpecificGroup(String $groupModality){

        $groupQuery = $this->linkpdo->prepare('SELECT id FROM visit_group WHERE study = :study AND group_modality=:groupModality');
        $groupQuery->execute(array('study' => $this->study, 'groupModality'=> $groupModality));
        $groupId=$groupQuery->fetch(PDO::FETCH_COLUMN);
        
        return new Visit_Group($this->linkpdo, $groupId);

    }

    public function getStudySpecificGroupManager(String $groupModality){

        $visitGroup=$this->getSpecificGroup($groupModality);
        
        return new Study_Visit_Manager($this, $visitGroup, $this->linkpdo);

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

    public function getStatistics() {
        return new Statistics($this, $this->linkpdo);
    }
    
    public function changeStudyActivation(bool $activated){
        $req = $this->linkpdo->prepare('UPDATE studies SET
    								active = :active
						        WHERE name = :study');
        $req->execute(array( 'study'=> $this->study, 'active'=>intval($activated)));
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