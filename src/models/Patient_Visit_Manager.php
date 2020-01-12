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

    private $patientCode;
    private $linkpdo;
    private $study;
    private $patientObject;
    private $visitGroup;

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
        $this->study = $this->patientObject->patientStudy;
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
													INNER JOIN visit_type ON (visit_type.name=visits.visit_type AND visit_type.group_id=visits.visit_group_id)
                                          			WHERE patient_code = :patientCode
                                                    AND visits.visit_group_id = :groupId
													AND visits.deleted=:deleted
													ORDER BY visit_type.visit_order');


        $visitQuery->execute(array(
            'patientCode' => $this->patientCode,
            'groupId' => $this->visitGroup->groupId,
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
     * Return array of non created visit name for current patient
     */
    public function getNotCreatedVisitName()
    {
        $allPossibleVisits=$this->visitGroup->getAllVisitTypesOfGroup();
        $createdVisits = $this->getCreatedPatientsVisits();

        $createdVisitName = array_map(function (Visit $visit) {
            return $visit->visitType;
        },  $createdVisits);

        $possibleVisitName = array_map(function (Visit_Type $visitType) {
            return $visitType->name;
        },  $allPossibleVisits);

        $missingVisitName = array_diff($possibleVisitName, $createdVisitName);

        return $missingVisitName;
    }

    //Retourne : la visite en attente de creation en 1er => celle apres la derniere crée et n+1 si visite optionnelle
    //et les visites suprimées
    //SK A Tester
    public function getAvailableVisitsToCreate()
    {
        $availableVisitName = [];

        // if withdraw disallow visit creation
        if ($this->patientObject->patientWithdraw) {
            $availableVisitName[] = Patient::PATIENT_WITHDRAW;
            return $availableVisitName;
        }

        $allPossibleVisits=$this->visitGroup->getAllVisitTypesOfGroup();
        $createdVisits = $this->getCreatedPatientsVisits();

        $createdVisitOrder = array_map(function (Visit $visit) {
            return $visit->getVisitCharacteristics()->visitOrder;
        },  $createdVisits);

        if(empty($createdVisitOrder)){
            $lastCreatedVisitOrder= -1;
        }else{
            $lastCreatedVisitOrder = max($createdVisitOrder);
        }


        foreach ($allPossibleVisits as $possibleVisit) {

            if ($possibleVisit->visitOrder < $lastCreatedVisitOrder) {
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
            $availableVisitName[] = "Error - Please check that the study contains possible visits";
        }

        return $availableVisitName;
    }

    /**
     * Return if there are still visits that are awaiting to be created for this patient
     * @return boolean
     */
    public function isMissingVisit()
    {
        if (sizeof($this->getNotCreatedVisitName()) > 0) return true;
        else return false;
    }

    /**
     * Determine Visit Status of a patient
     * Theorical date are calculated from registration date and compared to
     * acquisition date if visit created or actual date for non created visit
     */
    public function determineVisitStatus(String $visitName)
    {

        $registrationDate = $this->patientObject->getImmutableRegistrationDate();

        $visitType = new Visit_Type($this->linkpdo, $this->visitGroup->groupId, $visitName);

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
            $visitObject = Visit::getVisitbyPatientAndVisitName($this->patientCode, $visitName, $this->linkpdo);
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
}
