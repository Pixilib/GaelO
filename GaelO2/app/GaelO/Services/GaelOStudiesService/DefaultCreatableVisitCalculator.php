<?php

namespace App\GaelO\Services\GaelOStudiesService;

use App\GaelO\Constants\Constants;
use App\GaelO\Interfaces\Repositories\StudyRepositoryInterface;
use App\GaelO\Interfaces\Repositories\VisitRepositoryInterface;
use App\GaelO\Interfaces\Repositories\VisitTypeRepositoryInterface;

class DefaultCreatableVisitCalculator
{

    protected VisitRepositoryInterface $visitRepositoryInterface;
    protected VisitTypeRepositoryInterface $visitTypeRepositoryInterface;

    public function __construct(VisitRepositoryInterface $visitRepositoryInterface, VisitTypeRepositoryInterface $visitTypeRepositoryInterface)
    {
        $this->visitRepositoryInterface = $visitRepositoryInterface;
        $this->visitTypeRepositoryInterface = $visitTypeRepositoryInterface;
    }

    public function getAvailableVisitToCreate(array $patientEntity): array
    {

        //If status other than Included or Preincluded (ie : excluded or withdrawn) no visit is expected to be created
        if (!in_array($patientEntity['inclusion_status'], [Constants::PATIENT_INCLUSION_STATUS_INCLUDED, Constants::PATIENT_INCLUSION_STATUS_PRE_INCLUDED])) {
            return [];
        }

        //Get Created Patients Visits
        $createdVisitsArray = $this->visitRepositoryInterface->getPatientsVisits($patientEntity['id']);

        //Get possible visits groups and types from study
        $studyVisitsTypes = $this->visitTypeRepositoryInterface->getVisitTypesOfStudy($patientEntity['study_name']);

        $createdVisitMap = [];

        //Build array of Created visit Order indexed by visit group name
        foreach ($createdVisitsArray as $createdVisit) {
            $visitOrder = $createdVisit['visit_type']['order'];
            $visitGroupName = $createdVisit['visit_type']['visit_group']['name'];
            $createdVisitMap[$visitGroupName][] = $visitOrder;
        }



        $studyVisitMap = [];
        //Reindex possible visits by visit group name and order

        foreach ($studyVisitsTypes as $visitType) {

            $visitGroupName = $visitType['visit_group']['name'];
            $visitGroupModality = $visitType['visit_group']['modality'];

            $studyVisitMap[$visitGroupName][$visitType['order']] = [
                'groupId' => $visitType['visit_group_id'],
                'groupModality' => $visitGroupModality,
                'groupName' => $visitGroupName,
                'typeId' => $visitType['id'],
                'name' => $visitType['name'],
                'optional' => $visitType['optional']
            ];
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
