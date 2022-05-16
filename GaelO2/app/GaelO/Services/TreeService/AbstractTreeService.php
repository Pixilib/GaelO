<?php

namespace App\GaelO\Services\TreeService;

use App\GaelO\Constants\Constants;
use App\GaelO\Entities\PatientEntity;
use App\GaelO\Entities\StudyEntity;
use App\GaelO\Entities\VisitEntity;
use App\GaelO\Interfaces\Repositories\CenterRepositoryInterface;
use App\GaelO\Interfaces\Repositories\PatientRepositoryInterface;
use App\GaelO\Interfaces\Repositories\StudyRepositoryInterface;
use App\GaelO\Interfaces\Repositories\UserRepositoryInterface;
use App\GaelO\Interfaces\Repositories\VisitRepositoryInterface;

abstract class AbstractTreeService
{

    protected string $role;
    protected int $userId;
    protected StudyEntity $studyEntity;
    protected PatientRepositoryInterface $patientRepositoryInterface;
    protected VisitRepositoryInterface $visitRepositoryInterface;
    protected UserRepositoryInterface $userRepositoryInterface;
    protected StudyRepositoryInterface $studyRepositoryInterface;
    protected CenterRepositoryInterface $centerRepositoryInterface;

    public function __construct(UserRepositoryInterface $userRepositoryInterface, StudyRepositoryInterface $studyRepositoryInterface, PatientRepositoryInterface $patientRepositoryInterface, VisitRepositoryInterface $visitRepositoryInterface, CenterRepositoryInterface $centerRepositoryInterface)
    {
        $this->patientRepositoryInterface = $patientRepositoryInterface;
        $this->userRepositoryInterface = $userRepositoryInterface;
        $this->visitRepositoryInterface = $visitRepositoryInterface;
        $this->studyRepositoryInterface = $studyRepositoryInterface;
        $this->centerRepositoryInterface = $centerRepositoryInterface;
    }

    public function setUserAndStudy(int $userId, string $studyName)
    {
        $this->userId = $userId;
        $this->studyEntity = $this->studyRepositoryInterface->find($studyName);
    }

    protected function makePatientDetails(array $patientsEntities): array
    {
        $patientArray = [];
        foreach ($patientsEntities as $patientEntity) {
            $patient = new PatientEntity();
            $patient->code = $patientEntity['code'];
            $patient->id = $patientEntity['id'];
            $patient->fillCenterDetails($patientEntity['center']['name'], $patientEntity['center']['country_code']);
            $patientArray[$patient->id] = (array) $patient;
        }

        return $patientArray;
    }

    protected function makeVisitDetails(array $visitsEntities): array
    {
        $visitArray = [];
        foreach ($visitsEntities as $visitEntity) {
            $visit = new VisitEntity();
            $visit->fillForTree($visitEntity);
            $visit->setVisitContext($visitEntity['visit_type']['visit_group'], $visitEntity['visit_type']);
            $visit->reviewStatus = key_exists( 'review_status', $visitEntity ) ? $visitEntity['review_status']['review_status'] : Constants::REVIEW_STATUS_NOT_DONE ;
            $visitArray[] = (array) $visit;
        }

        return $visitArray;
    }

    protected function formatResponse(array $visitsEntities): array
    {
        $patientIdArray = array_map(function ($visit) {
            return $visit['patient_id'];
        }, $visitsEntities);


        $patientsEntities = $this->patientRepositoryInterface->getPatientsFromIdArray($patientIdArray, true);


        return [
            'patients' => $this->makePatientDetails($patientsEntities),
            'visits' => $this->makeVisitDetails($visitsEntities)
        ];
    }

    public abstract function buildTree(): array;
}
