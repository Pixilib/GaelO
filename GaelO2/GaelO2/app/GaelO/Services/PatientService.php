<?php

namespace App\GaelO\Services;

use App\GaelO\Constants\Constants;
use App\GaelO\Interfaces\Repositories\PatientRepositoryInterface;
use App\GaelO\Interfaces\Repositories\StudyRepositoryInterface;
use App\GaelO\Interfaces\Repositories\VisitRepositoryInterface;

class PatientService
{

    private PatientRepositoryInterface $patientRepositoryInterface;
    private VisitRepositoryInterface $visitRepositoryInterface;
    private StudyRepositoryInterface $studyRepositoryInterface;

    private int $patientCode;

    public function __construct(PatientRepositoryInterface $patientRepositoryInterface, VisitRepositoryInterface $visitRepositoryInterface, StudyRepositoryInterface $studyRepositoryInterface)
    {
        $this->patientRepositoryInterface = $patientRepositoryInterface;
        $this->visitRepositoryInterface = $visitRepositoryInterface;
        $this->studyRepositoryInterface = $studyRepositoryInterface;
    }

    public function setPatientCode(int $patientCode): void
    {
        $this->patientCode = $patientCode;
    }


    public function getAvailableVisitToCreate(): array
    {

        $patientEntity = $this->patientRepositoryInterface->find($this->patientCode);

        //If Patient status different from Included, No further visit creation is possible
        if ($patientEntity['inclusion_status'] !== Constants::PATIENT_INCLUSION_STATUS_INCLUDED) {
            return [];
        }

        //Get Created Patient's Visits
        $createdVisitsArray = $this->visitRepositoryInterface->getPatientsVisits($this->patientCode);

        $createdVisitMap = [];

        //Build array of Created visit Order indexed by visit group modality
        foreach ($createdVisitsArray as $createdVisit) {
            $visitOrder = $createdVisit['visit_type']['order'];
            $modality = $createdVisit['visit_type']['visit_group']['modality'].' - '.$createdVisit['visit_type']['visit_group']['name'];
            $createdVisitMap[$modality][] = $visitOrder;
        }


        //Get Possibles visits groups and type from study
        $studyVisitsDetails = $this->studyRepositoryInterface->getStudyDetails($patientEntity['study_name']);
        $studyVisitMap = [];
        //Reindex possibiles visits by modality and order
        foreach ($studyVisitsDetails['visit_group_details'] as $visitGroupDetails) {

            foreach ($visitGroupDetails['visit_types'] as $visitType) {

                $studyVisitMap[$visitGroupDetails['modality'].' - '.$visitGroupDetails['name']][$visitType['order']] = [
                    'groupId' => $visitType['visit_group_id'],
                    'typeId' => $visitType['id'],
                    'name' => $visitType['name']
                ];
            }
        }
        $visitToCreateMap = [];

        //Search for visits that have not been created
        foreach ($studyVisitMap as $modality => $visitsArray) {

            foreach ($visitsArray as $visitOrder => $visit) {
                if (!isset($createdVisitMap[$modality]) || !in_array($visitOrder, $createdVisitMap[$modality])) {
                    $visit['modality'] = $modality;
                    $visit['order'] = $visitOrder;
                    $visitToCreateMap[] = $visit;
                }
            }
        }

        return $visitToCreateMap;
    }
}
