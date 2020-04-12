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

class Tree
{
  private $role;
  private $username;
  private $studyObject;
  private $linkpdo;

  public function __construct(string $role, string $username, string $study, PDO $linkpdo)
  {
	$this->linkpdo=$linkpdo;
	$this->role=$role;
	$this->username=$username;
	$this->studyObject=new Study($study, $linkpdo);
  }

  /**
   * Determine class value of Investigator and Controller visit item
   * to make specific color decoration depending on status of visit
   */
  private function determineClassOfVisit(Visit $visitObject): String
  {

	if ($this->role == User::INVESTIGATOR) {

	  //Add upload status / user form in class 
	  if ($visitObject->statusDone == Visit::DONE && $visitObject->uploadStatus == Visit::NOT_DONE && $visitObject->stateInvestigatorForm != Visit::DONE) {
		$class="NotBoth";
	  }else if ($visitObject->statusDone == Visit::DONE && $visitObject->stateInvestigatorForm != Visit::DONE) {
		$class="NotForm";
	  }else if ($visitObject->statusDone == Visit::DONE && $visitObject->uploadStatus == Visit::NOT_DONE) {
		$class="NotUpload";
	  }else {
		$class="OK";
	  }
	}else if ($this->role == User::CONTROLLER) {
	  if ($visitObject->stateQualityControl == Visit::QC_ACCEPTED || $visitObject->stateQualityControl == Visit::QC_REFUSED) {
		$class="OK";
	  }else if ($visitObject->stateQualityControl == Visit::QC_NOT_DONE || $visitObject->stateQualityControl == Visit::QC_WAIT_DEFINITVE_CONCLUSION) {
		$class="NotBoth";
	  }
	}else if ($this->role == User::REVIEWER) {
	  //Add status of review process (need to remove space from status string)
	  $class=str_replace(" ", "", $visitObject->reviewStatus); ;
	}

	return $class;
  }

  /**
   * Create visit entry in Tree from a visit Object
   */
  private function visitObjectToTreeObject(Visit $visitObject)
  {

	$jsonVisitLevel['id']=$visitObject->id_visit;
	$jsonVisitLevel['parent']=$visitObject->patientCode.'_'.$visitObject->visitGroupObject->groupModality;
	$jsonVisitLevel['icon']='/assets/images/report-icon.png';
	$jsonVisitLevel['text']=$visitObject->visitType;
	$jsonVisitLevel['level']='visit';
	$jsonVisitLevel['state']['opened']=false;

	if ($this->role == User::INVESTIGATOR || $this->role == User::CONTROLLER || $this->role == User::REVIEWER) {
	  //NB SI BESOIN ON PEUT AJOUTER UN CUSTOM ATRRIBUT A LA PLACE DE class
	  $attr['class']=$this->determineClassOfVisit($visitObject);
	  $jsonVisitLevel['li_attr']=$attr;
	}

	return $jsonVisitLevel;
  }

  /**
   * Create a patient entry in Tree
   */
  private function patientObjectToTreeObject(String $patientCode)
  {

	$jsonPatientLevel['id']=$patientCode;
	$jsonPatientLevel['parent']='#';
	$jsonPatientLevel['icon']='/assets/images/person-icon.png';
	$jsonPatientLevel['text']=$patientCode;
	$jsonPatientLevel['level']='patient';
	$jsonPatientLevel['state']['opened']=false;

	return $jsonPatientLevel;
  }

  /**
   * Create a Visit group entry in tree
   */
  private function visitGroupToTreeObject($patientCode, $groupModality)
  {

	$jsonGroupLevel['id']=$patientCode.'_'.$groupModality;
	$jsonGroupLevel['parent']=$patientCode;
	$jsonGroupLevel['icon']='/assets/images/person-icon.png';
	$jsonGroupLevel['text']=$groupModality;
	$jsonGroupLevel['level']='visit_group';
	$jsonGroupLevel['state']['opened']=true;


	return $jsonGroupLevel;
  }

  /**
   * sort Visits in key by modality
   */
  private function processVisitsArray($visitsArray)
  {

	$sortedModalities=[];

	foreach ($visitsArray as $visitObject) {
	  $sortedModalities[$visitObject->visitGroupObject->groupModality][]=$visitObject;
	}

	return $sortedModalities;
  }

  /**
   * Select visit from patients for some roles 
   */
  private function makeTreeFromPatients($patientsArray)
  {

	$resultTree=[];
	foreach ($patientsArray as $patientObject) {
	  //If investigator display all created visits
	  if ($this->role == User::INVESTIGATOR) $visitsArray=$patientObject->getAllCreatedPatientsVisits();
	  //if Reviewer display all QC accepted visits
	  if ($this->role == User::REVIEWER) $visitsArray=$patientObject->getAllQcDonePatientsVisits();
	  $stortedVisits=$this->processVisitsArray($visitsArray);
	  $resultTree[$patientObject->patientCode]['patientObject']=$patientObject;
	  $resultTree[$patientObject->patientCode]['modalities']=$stortedVisits;
	}

	return $resultTree;
  }

  /**
   * Sort an array of Visits by patient code
   */
  private function makeTreeFromVisits($visitsArray)
  {

	$resultTree=[];

	foreach ($visitsArray as $visitObject) {
	  $resultTree[$visitObject->patientCode]['patientObject']=$visitObject->getPatient();
	  $resultTree[$visitObject->patientCode]['modalities'][$visitObject->visitGroupObject->groupModality][]=$visitObject;
	}

	return $resultTree;
  }

  /**
   * Generate the final JSON tree by adding patient, modality and visit items
   */
  private function treeStructuretoJsonTree($treeStructure)
  {
	$jsonTree=[];
	foreach ($treeStructure as $patientCode => $patientData) {
	  $jsonTree[]=$this->patientObjectToTreeObject($patientCode);
	  foreach ($patientData['modalities'] as $modality => $visitObjects) {
		$jsonTree[]=$this->visitGroupToTreeObject($patientCode, $modality);
		foreach ($visitObjects as $visitObject) {
		  $jsonTree[]=$this->visitObjectToTreeObject($visitObject);
		}
	  }
	}

	return $jsonTree;
  }

  /**
   * Return JSON for JSTree according to role  (patient + Visit)
   * @return array
   */
  public function buildTree()
  {

	$possibleVisitGroups=$this->studyObject->getAllPossibleVisitGroups();

	if ($this->role == User::INVESTIGATOR) {
	  //retrieve from DB the patient's list of the requested study and included in user's center or affiliated centers

	  $patientObjectArray=$this->studyObject->getPatientsLinkedToUserCenters($this->username);

	  $treeStructure=$this->makeTreeFromPatients($patientObjectArray);
	}else if ($this->role == User::CONTROLLER) {

	  $controllerVisitsArray=[];

	  foreach ($possibleVisitGroups as $visitGroup) {
		$studyVisitManager=$visitGroup->getStudyVisitManager();
		$visitsArray=$studyVisitManager->getVisitForControllerAction();
		array_push($controllerVisitsArray, ...$visitsArray);
	  }

	  $treeStructure=$this->makeTreeFromVisits($controllerVisitsArray);
	}else if ($this->role == User::MONITOR) {

	  $monitorVisitsArray=[];

	  foreach ($possibleVisitGroups as $visitGroup) {
		$studyVisitManager=$visitGroup->getStudyVisitManager();
		$visitsArray=$studyVisitManager->getCreatedVisits();
		array_push($monitorVisitsArray, ...$visitsArray);
	  }

	  $treeStructure=$this->makeTreeFromVisits($monitorVisitsArray);
	}else if ($this->role == User::REVIEWER) {
	  //SK attention une review pending fait reafficher toutes les reviews du patient
	  //que soit la modalite
	  //peut etre jouer avec le job des users pour filtrer le group de visite

	  $patientsList=[];
	  //For each visit group list patient having a least on visit awaiting review
	  foreach ($possibleVisitGroups as $visitGroup) {
		$studyVisitManager=$visitGroup->getStudyVisitManager();
		$visits=$studyVisitManager->getAwaitingReviewVisit($this->username);
		if (!empty($visits)) {
		  foreach ($visits as $visitObject) {
			$patientsList[$visitObject->patientCode]=$visitObject->patientCode;
		  }
		}
	  }
	  //extract unique patient array
	  $patientCodeArray=array_keys($patientsList);
	  $patientObjectArray=[];
	  foreach ($patientCodeArray as $patientCode) {
		$patientObjectArray[]=new Patient($patientCode, $this->linkpdo);
	  }

	  $treeStructure=$this->makeTreeFromPatients($patientObjectArray);

	}

	$jsonTree=$this->treeStructuretoJsonTree($treeStructure);

	return  $jsonTree;
  }
}
