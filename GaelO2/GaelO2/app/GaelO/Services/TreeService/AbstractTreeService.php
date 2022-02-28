<?php

namespace App\GaelO\Services\TreeService;

use App\GaelO\Entities\StudyEntity;
use App\GaelO\Interfaces\Repositories\PatientRepositoryInterface;
use App\GaelO\Interfaces\Repositories\StudyRepositoryInterface;
use App\GaelO\Interfaces\Repositories\UserRepositoryInterface;
use App\GaelO\Interfaces\Repositories\VisitRepositoryInterface;

abstract class AbstractTreeService {

    protected string $role;
    protected int $userId;
    protected StudyEntity $studyEntity;
    protected PatientRepositoryInterface $patientRepositoryInterface;
    protected VisitRepositoryInterface $visitRepositoryInterface;
    protected UserRepositoryInterface $userRepositoryInterface;
    protected StudyRepositoryInterface $studyRepositoryInterface;

    public function __construct(UserRepositoryInterface $userRepositoryInterface, StudyRepositoryInterface $studyRepositoryInterface, PatientRepositoryInterface $patientRepositoryInterface, VisitRepositoryInterface $visitRepositoryInterface)
    {
        $this->patientRepositoryInterface = $patientRepositoryInterface;
        $this->userRepositoryInterface = $userRepositoryInterface;
        $this->visitRepositoryInterface = $visitRepositoryInterface;
        $this->studyRepositoryInterface = $studyRepositoryInterface;
    }

    public function setUserAndStudy(int $userId, string $studyName)
    {
        $this->userId = $userId;
        $this->studyEntity = $this->studyRepositoryInterface->find($studyName);
    }

    protected function makeTreeFromVisits(array $visitsArray): array
    {

        $responseArray = [];
        $responseArray['visits'] = [];
        $responseArray['patients'] = [];

        $patientIdsArray = array_unique(array_map(function ($visit) {
            return $visit['patient_id'];
        }, $visitsArray));

        $patientsArray = $this->patientRepositoryInterface->getPatientsFromIdArray($patientIdsArray);
        foreach($patientsArray as $patientEntity) {
            $responseArray['patients'][$patientEntity['id']] = $patientEntity['code'];
        }

        foreach ($visitsArray as $visitObject) {
            $visitsFormattedData = (array) TreeItem::createItem($visitObject);
            $responseArray['visits'][] = $visitsFormattedData;
        }

        return $responseArray;
    }

    public abstract function buildTree() : array;

}
