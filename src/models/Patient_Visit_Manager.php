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

class Patient_Visit_Manager
{

	protected $patientCode;
	protected $linkpdo;
	protected $patientObject;
	protected $visitGroup;

	//Constants visit status available
	const DONE = "Done";
	const NOT_DONE = "Not Done";
	const SHOULD_BE_DONE = "Should be done";
	const PENDING = "Pending";
	const COMPLIANCY_YES = "Yes";
	const COMPLIANCY_NO = "No";
	const VISIT_WITHDRAWN = "Visit Withdrawn";
	const VISIT_POSSIBLY_WITHDRAWN = "Possibly Withdrawn";
	const OPTIONAL_VISIT = "Optional";
	//Not needed status is no make custom choice to deactivate upload reminder
	const VISIT_NOT_NEEDED="Not Nedded";

	public function __construct(Patient $patientObject, Visit_Group $visitGroup, $linkpdo)
	{
		$this->linkpdo = $linkpdo;
		$this->patientCode = $patientObject->patientCode;
		$this->patientObject = $patientObject;
		$this->visitGroup = $visitGroup;
	}


	/**
	 * Return created visits of a given patient
	 * @param bool $deletedVisits
	 * @return Visit[]
	 */
	public function getCreatedPatientsVisits(bool $deletedVisits = false) : Array
	{

		$visitQuery = $this->linkpdo->prepare('SELECT id_visit FROM visits
                                                                INNER JOIN visit_type ON 
                                                                (visit_type.id=visits.visit_type_id 
                                                                AND visit_type.group_id = :visitGroupId)
													    WHERE patient_code = :patientCode
													    AND visits.deleted=:deleted
													    ORDER BY visit_type.visit_order');


		$visitQuery->execute(array(
			'patientCode' => $this->patientCode,
			'visitGroupId' => $this->visitGroup->groupId,
			'deleted' => $deletedVisits
		));

		$visitsResults = $visitQuery->fetchAll(PDO::FETCH_COLUMN);

		$visitsObjectArray = [];
		foreach ($visitsResults as $idVisit) {
			$visitsObjectArray[] = new Visit($idVisit, $this->linkpdo);
		}

		return $visitsObjectArray;
	}

	/**
	 * Return uploaded visits of a given patient
	 * @param bool $deletedVisits
	 * @return Visit[]
	 */
	public function getQcDonePatientsVisits(bool $deletedVisits = false) : Array
	{

		$visitQuery = $this->linkpdo->prepare('SELECT id_visit FROM visits
													            INNER JOIN visit_type ON 
                                                                (visit_type.id=visits.visit_type_id 
                                                                AND visit_type.group_id = :visitGroupId)
                                                    WHERE patient_code = :patientCode
                                                    AND state_quality_control = :qcStatus
													AND deleted=:deleted
													ORDER BY visit_order');


		$visitQuery->execute(array(
			'patientCode' => $this->patientCode,
			'qcStatus' => Visit::QC_ACCEPTED,
			'visitGroupId' => $this->visitGroup->groupId,
			'deleted' => $deletedVisits
		));

		$visitsResults = $visitQuery->fetchAll(PDO::FETCH_COLUMN);

		$visitsObjectArray = [];
		foreach ($visitsResults as $idVisit) {
			$visitsObjectArray[] = new Visit($idVisit, $this->linkpdo);
		}

		return $visitsObjectArray;
	}

	/**
	 * Return array of available visits to create
	 * Throw exception is patient withdraw or no possible visits
	 * Can be overriden for custom visit creation workflow
	 */
	public function getAvailableVisitsToCreate() : Array
	{
		$availableVisitName = [];

		// if withdraw disallow visit creation
		if ($this->patientObject->patientWithdraw) {
			throw new Exception(Patient::PATIENT_WITHDRAW);
		}

		$allPossibleVisits=$this->visitGroup->getAllVisitTypesOfGroup();
		$createdVisits = $this->getCreatedPatientsVisits();
		$createdVisitsNameArray=array_map(function (Visit $visit) {
			return $visit->visitType;
		},  $createdVisits);

		$createdVisitOrder = array_map(function (Visit $visit) {
			return $visit->getVisitCharacteristics()->visitOrder;
		},  $createdVisits);

		if(empty($createdVisitOrder)){
			$lastCreatedVisitOrder= -1;
		}else{
			$lastCreatedVisitOrder = max($createdVisitOrder);
		}

		foreach ($allPossibleVisits as $possibleVisit) {

			if(in_array($possibleVisit->name, $createdVisitsNameArray) ){
				//Already created do not display it
				continue;
			}

			if ($possibleVisit->visitOrder < $lastCreatedVisitOrder ) {
				$availableVisitName[] = $possibleVisit->name;
			} else if($possibleVisit->visitOrder > $lastCreatedVisitOrder) {
				if ($possibleVisit->optionalVisit) {
					//If optional add optional visit and look for the next order
					$availableVisitName[] = $possibleVisit->name;
					$lastCreatedVisitOrder++;
				} else if ($possibleVisit->visitOrder > $lastCreatedVisitOrder) {
					$availableVisitName[] = $possibleVisit->name;
					break;
				}
			}
		}

		//Reverse to sort for the more advanced visit to create
		$availableVisitName = array_reverse($availableVisitName);

		if (empty($availableVisitName)) {
			throw new Exception('No possible visit');
		}

		return $availableVisitName;
	}

	/**
	 * Return if there are still visits that are awaiting to be created for this patient
	 * @return boolean
	 */
	public function isMissingVisit() : bool
	{
		try{
			if( ! empty( $this->getAvailableVisitsToCreate() ) ) {
				return true;
			}
		}catch (Exception $e) {
			//if exception happens no visits are missing
			return false;
		}
	}

	/**
	 * Determine Visit Status of a patient
	 * Theorical date are calculated from registration date and compared to
	 * acquisition date if visit created or actual date for non created visit
	 */
	public function determineVisitStatus(String $visitName)
	{

		$registrationDate = $this->patientObject->getImmutableRegistrationDate();
		$visitType = Visit_Type::getVisitTypeByName($this->visitGroup->groupId, $visitName, $this->linkpdo);

		$dateDownLimit = $registrationDate->modify($visitType->limitLowDays . 'day');
		$dateUpLimit = $registrationDate->modify($visitType->limitUpDays . 'day');

		$visitAnswer['status'] = null;
		$visitAnswer['compliancy'] = null;
		$visitAnswer['shouldBeDoneBefore'] = $dateUpLimit->format('Y-m-d');
		$visitAnswer['shouldBeDoneAfter'] = $dateDownLimit->format('Y-m-d');
		$visitAnswer['state_investigator_form'] = null;
		$visitAnswer['state_quality_control'] = null;
		$visitAnswer['acquisition_date'] = null;
		$visitAnswer['upload_date'] = null;
		$visitAnswer['upload_status'] = null;
		$visitAnswer['id_visit'] = null;

		try {
			//Visit Created check compliancy
			$visitObject = $this->getCreatedVisitForVisitTypeId($visitType->id);
			$visitAnswer['state_investigator_form'] = $visitObject->stateInvestigatorForm;
			$visitAnswer['state_quality_control'] = $visitObject->stateQualityControl;
			$visitAnswer['acquisition_date'] = $visitObject->acquisitionDate;
			$visitAnswer['upload_date'] = $visitObject->uploadDate;
			$visitAnswer['upload_status'] = $visitObject->uploadStatus;
			$visitAnswer['id_visit'] = $visitObject->id_visit;
			$testedDate = $visitObject->acquisitionDate;
			$visitAnswer['status'] = Patient_Visit_Manager::DONE;

			if ($testedDate >= $dateDownLimit && $testedDate <= $dateDownLimit) {
				$visitAnswer['compliancy'] = Patient_Visit_Manager::COMPLIANCY_YES;
			} else {
				$visitAnswer['compliancy'] = Patient_Visit_Manager::COMPLIANCY_NO;
			}
		} catch (Exception $e) {
			//Visit Not Created
			//If optional visit no status determination
			if ($visitType->optionalVisit) {
				$visitAnswer['status'] = Patient_Visit_Manager::OPTIONAL_VISIT;
			} else {
				//Compare actual time with theorical date to determine status
				$testedDate = new DateTime(date("Y-m-d"));
				if ($testedDate <= $dateUpLimit) {
					$visitAnswer['status'] = Patient_Visit_Manager::PENDING;
				} else {
					$visitAnswer['status'] = Patient_Visit_Manager::SHOULD_BE_DONE;
				}
			}
		}

		//Take account of possible withdrawal if not created
		if ($this->patientObject->patientWithdraw &&  $visitAnswer['acquisition_date'] == null) {
			if ($this->patientObject->patientWithdrawDate < $dateDownLimit) {
				$visitAnswer['status'] = Patient_Visit_Manager::VISIT_WITHDRAWN;
			} else if ($this->patientObject->patientWithdrawDate > $dateDownLimit) {
				$visitAnswer['status'] = Patient_Visit_Manager::VISIT_POSSIBLY_WITHDRAWN;
			}
		}

		return $visitAnswer;
	}

	/**
	 * Return visits of this patient available for review
	 */
	public function getAwaitingReviewVisits()
	{

		$createdVisits = $this->getCreatedPatientsVisits();

		$availableVisitsForReview = [];

		foreach ($createdVisits as $visit) {
			if ($visit->reviewAvailable) {
				$availableVisitsForReview[] = $visit;
			}
		}

		return $availableVisitsForReview;
	}

	public function isHavingAwaitingReviewVisit()
	{
		$awaitingReviews = $this->getAwaitingReviewVisits();
		return (!empty($awaitingReviews));
	}

	public function getCreatedVisitForVisitTypeId($visitTypeId){
		$visitQuery = $this->linkpdo->prepare ( 'SELECT id_visit FROM visits WHERE patient_code=:patientCode AND visit_type_id=:visitTypeId AND deleted=0 ' );
        
		$visitQuery->execute ( array('patientCode' => $this->patientCode, 'visitTypeId'=>$visitTypeId) );
		$visitId = $visitQuery->fetch(PDO::FETCH_COLUMN);

		if(empty($visitId)){
			throw new Exception("Visit Non Existing");
		}else{
			return new Visit($visitId, $this->linkpdo);
		}
        
	}

	public function getCreatedVisitByVisitName($visitName){

		$visitQuery = $this->linkpdo->prepare ( 'SELECT id_visit FROM visits, visit_type WHERE visits.visit_type_id = visit_type.id AND visits.patient_code=:patientCode AND visit_type.name=:visitName AND visits.deleted=0 ' );
        
		$visitQuery->execute ( array('patientCode' => $this->patientCode, 'visitName'=>$visitName) );
		$visitId = $visitQuery->fetch(PDO::FETCH_COLUMN);

		if(empty($visitId)){
			throw new Exception("Visit Non Existing");
		}else{
			return new Visit($visitId, $this->linkpdo);
		}

	}
}
