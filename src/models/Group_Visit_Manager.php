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
 * Manage visit for a group level of a study
 */

class Group_Visit_Manager
{

	private Study $studyObject;
	private Visit_Group $visitGroupObject;
	private PDO $linkpdo;


	public function __construct(Study $studyObject, Visit_Group $visitGroupObject, PDO $linkpdo)
	{
		$this->linkpdo=$linkpdo;
		$this->studyObject=$studyObject;
		$this->visitGroupObject=$visitGroupObject;
	}

	public function getVisitGroupObject(): Visit_Group
	{
		return $this->visitGroupObject;
	}

	/**
	 * Return uploaded and non deleted visit Objects (visit Done and Upload status done)
	 */
	public function getUploadedVisits(): array
	{

		$uploadedVisitQuery=$this->linkpdo->prepare('SELECT id_visit FROM visits 
                                                                    INNER JOIN visit_type ON 
                                                                        (visit_type.id=visits.visit_type_id 
                                                                        AND visit_type.group_id = :visitGroupId)
                                                                WHERE deleted=0
                                                                    AND upload_status="Done" ');

		$uploadedVisitQuery->execute(array(
			'visitGroupId' => $this->visitGroupObject->groupId
		));

		$uploadedVisitIds=$uploadedVisitQuery->fetchall(PDO::FETCH_COLUMN);

		$visitObjectArray=[];
		foreach ($uploadedVisitIds as $id_visit) {
			$visitObjectArray[]=new Visit($id_visit, $this->linkpdo);
		}

		return $visitObjectArray;
	}

	/**
	 * Get visits with a awaiting upload status (Visit Done but upload status not done)
	 */
	public function getAwaitingUploadVisit(): array
	{

		$uploadedVisitQuery=$this->linkpdo->prepare("SELECT id_visit FROM visits 
                                                                    INNER JOIN visit_type ON 
                                                                         (visit_type.id=visits.visit_type_id 
                                                                            AND visit_type.group_id = :visitGroupId)
                                                                WHERE deleted=0
														        AND upload_status = 'Not Done'
														        AND status_done = 'Done' ");

		$uploadedVisitQuery->execute(
			array(
				'visitGroupId' => $this->visitGroupObject->groupId
			)
		);
		$uploadedVisitIds=$uploadedVisitQuery->fetchAll(PDO::FETCH_COLUMN);

		$visitObjectArray=[];
		foreach ($uploadedVisitIds as $id_visit) {
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
	public function getAwaitingReviewVisit(string $username=null): array
	{

		//Query visit to analyze visit awaiting a review
		$idVisitsQuery=$this->linkpdo->prepare('SELECT id_visit FROM visits 
                                                                    INNER JOIN visit_type ON 
                                                                        (visit_type.id=visits.visit_type_id 
                                                                        AND visit_type.group_id = :visitGroupId)
                                                                WHERE deleted=0
                                                                AND review_available=1 
                                                                ORDER BY visit_type.visit_order ');

		$idVisitsQuery->execute(array(
			'visitGroupId' => $this->visitGroupObject->groupId
		));

		$visitList=$idVisitsQuery->fetchAll(PDO::FETCH_COLUMN);

		$visitObjectArray=[];

		foreach ($visitList as $visitId) {
			$visitObject=new Visit($visitId, $this->linkpdo);

			if (!empty($username)) {
				if ($visitObject->isAwaitingReviewForReviewerUser($username)) $visitObjectArray[]=$visitObject;
			}else {
				$visitObjectArray[]=$visitObject;
			}
		}

		return $visitObjectArray;
	}

	/**
	 * For controller tree
	 * List all visits that awaiting QC Action (QC not done or waiting definitive conclusion)
	 */
	public function getVisitForControllerAction(): array
	{

		$visitsQuery=$this->linkpdo->prepare('SELECT id_visit FROM visits 
                                                    INNER JOIN visit_type ON 
                                                        (visit_type.id=visits.visit_type_id 
                                                        AND visit_type.group_id = :visitGroupId)
                                                    WHERE deleted=0
                                                    AND status_done = "Done"
                                                    AND upload_status= "Done"
                                                    AND state_investigator_form = "Done"
                                                    AND (state_quality_control = "Not Done"
                                                        OR state_quality_control = "Wait Definitive Conclusion")
                                                    ORDER BY visit_type.visit_order');
		$visitsQuery->execute(array(
			'visitGroupId' => $this->visitGroupObject->groupId
		));

		$visits=$visitsQuery->fetchAll(PDO::FETCH_COLUMN);

		$visitObjectArray=[];
		foreach ($visits as $visit) {
			$visitObjectArray[]=new Visit($visit, $this->linkpdo);
		}

		return $visitObjectArray;
	}

	/**
	 * Return visits with a specific QC Status
	 */
	public function getVisitWithQCStatus($qcStatus): array
	{

		$visitQuery=$this->linkpdo->prepare("SELECT id_visit FROM visits 
                                                        INNER JOIN visit_type ON 
                                                            (visit_type.id=visits.visit_type_id 
                                                            AND visit_type.group_id = :visitGroupId)
                                                        WHERE deleted=0
                                                        AND state_quality_control=:qcStatus");

		$visitQuery->execute(array(
			'study' => $this->study,
			'qcStatus' => $qcStatus,
			'visitGroupId' => $this->visitGroupObject->groupId
		));
		$visitIds=$visitQuery->fetchall(PDO::FETCH_COLUMN);

		$visitObjectArray=[];
		foreach ($visitIds as $id_visit) {
			$visitObjectArray[]=new Visit($id_visit, $this->linkpdo);
		}

		return $visitObjectArray;
	}

	/**
	 * Get Visits with image uploaded status but investigator form missing
	 */
	public function getVisitsMissingInvestigatorForm(): array
	{

		$visitQuery=$this->linkpdo->prepare("SELECT id_visit FROM visits 
                                                            INNER JOIN visit_type ON 
                                                                (visit_type.id=visits.visit_type_id 
                                                                AND visit_type.group_id = :visitGroupId)
                                                            WHERE deleted=0 
                                                            AND state_investigator_form !='Done' 
                                                            AND upload_status='Done'");

		$visitQuery->execute(array(
			'visitGroupId' => $this->visitGroupObject->groupId
		));

		$visitIds=$visitQuery->fetchAll(PDO::FETCH_COLUMN);

		$visitObjectArray=[];
		foreach ($visitIds as $id_visit) {
			$visitObjectArray[]=new Visit($id_visit, $this->linkpdo);
		}

		return $visitObjectArray;
	}

	/**
	 * Return studie's created visits
	 */
	public function getCreatedVisits(bool $deleted=false): array
	{

		$uploadedVisitQuery=$this->linkpdo->prepare('SELECT id_visit FROM visits 
                                                    INNER JOIN visit_type ON 
                                                        (visit_type.id=visits.visit_type_id 
                                                        AND visit_type.group_id = :visitGroupId)
                                                    AND deleted = :deleted 
                                                    ORDER BY patient_code, visit_type.visit_order');

		$uploadedVisitQuery->execute(array(
			'deleted' => intval($deleted),
			'visitGroupId' => $this->visitGroupObject->groupId
		));

		$uploadedVisitIds=$uploadedVisitQuery->fetchAll(PDO::FETCH_COLUMN);

		$visitObjectArray=[];
		foreach ($uploadedVisitIds as $id_visit) {
			$visitObjectArray[]=new Visit($id_visit, $this->linkpdo);
		}

		return $visitObjectArray;
	}

	/**
	 * Determine all patient status for a specific visitType
	 */
	public function getPatientVisitStatusForVisitType(Visit_Type $visitType)
	{

		//Get patients list in this study
		$allPatients=$this->studyObject->getAllPatientsInStudy();

		$results=[];

		foreach ($allPatients as $patient) {

			$patientCenter=$patient->getPatientCenter();
			$visitManager=$patient->getPatientVisitManager($this->visitGroupObject);

			$patientData=[];
			$patientData['center']=$patientCenter->name;
			$patientData['country']=$patientCenter->countryName;
			$patientData['firstname']=$patient->patientFirstName;
			$patientData['lastname']=$patient->patientLastName;
			$patientData['birthdate']=$patient->patientBirthDate;
			$patientData['registration_date']=$patient->patientRegistrationDate;

			$visitStatus=$visitManager->determineVisitStatus($visitType->name);

			$results[$patient->patientCode]=array_merge($patientData, $visitStatus);
		}

		return $results;
	}

	/**
	 * Determine all visits status for all visit type for all patients
	 */
	public function getPatientsAllVisitsStatus()
	{

		//Get ordered list of possible visits in this study
		$allVisitsType=$this->visitGroupObject->getAllVisitTypesOfGroup();

		$results=[];

		foreach ($allVisitsType as $visitType) {

			$allPatientStatus=$this->getPatientVisitStatusForVisitType($visitType);

			$results[$visitType->name]=$allPatientStatus;
			//array_push($results, ...$allPatientStatus);
		}

		return $results;
	}
}
