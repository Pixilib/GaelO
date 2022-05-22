<?php

namespace App\GaelO\Services\GaelOStudiesService;

use App\GaelO\Constants\Constants;
use App\GaelO\Interfaces\Repositories\StudyRepositoryInterface;
use App\GaelO\Interfaces\Repositories\VisitRepositoryInterface;

class DefaultCreatableVisitCalculator {

    protected VisitRepositoryInterface $visitRepositoryInterface;
    protected StudyRepositoryInterface $studyRepositoryInterface;

    public function __construct(VisitRepositoryInterface $visitRepositoryInterface, StudyRepositoryInterface $studyRepositoryInterface)
    {
        $this->visitRepositoryInterface = $visitRepositoryInterface;
        $this->studyRepositoryInterface = $studyRepositoryInterface;
    }

    public function getAvailableVisitToCreate(array $patientEntity): array
    {

        //If status other than Included or Preincluded (ie : excluded or withdrawn) no visit is expected to be created
        if (!in_array($patientEntity['inclusion_status'], [Constants::PATIENT_INCLUSION_STATUS_INCLUDED, Constants::PATIENT_INCLUSION_STATUS_PRE_INCLUDED])) {
            return [];
        }

        //Get Created Patients Visits
        $createdVisitsArray = $this->visitRepositoryInterface->getPatientsVisits($patientEntity['id']);

        $createdVisitMap = [];

        //Build array of Created visit Order indexed by visit group name
        foreach ($createdVisitsArray as $createdVisit) {
            $visitOrder = $createdVisit['visit_type']['order'];
            $visitGroupName = $createdVisit['visit_type']['visit_group']['name'];
            $createdVisitMap[$visitGroupName][] = $visitOrder;
        }


        //Get possible visits groups and types from study
        $studyVisitsDetails = $this->studyRepositoryInterface->getStudyDetails($patientEntity['study_name']);
        $studyVisitMap = [];
        //Reindex possible visits by visit group name and order
        foreach ($studyVisitsDetails['visit_group_details'] as $visitGroupDetails) {

            foreach ($visitGroupDetails['visit_types'] as $visitType) {

                $studyVisitMap[$visitGroupDetails['name']][$visitType['order']] = [
                    'groupId' => $visitType['visit_group_id'],
                    'groupModality' => $visitGroupDetails['modality'],
                    'groupName' => $visitGroupDetails['name'],
                    'typeId' => $visitType['id'],
                    'name' => $visitType['name'],
                    'optional' => $visitType['optional']
                ];
            }
        }

        $visitToCreateMap = [];

        //Search for visits that have not been created
        foreach ($studyVisitMap as $visitGroupName => $visitsArray) {

            foreach ($visitsArray as $visitOrder => $visit) {
                if (!isset($createdVisitMap[$visitGroupName]) || !in_array($visitOrder, $createdVisitMap[$visitGroupName])) {
                    $visit['order'] = $visitOrder;
                    $visitToCreateMap[] = $visit;
                }
            }
        }

        return $visitToCreateMap;
    }


}
