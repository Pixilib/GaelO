<?php

namespace App\GaelO\Services\TreeService;

use App\GaelO\Repositories\PatientRepository;
use App\GaelO\Repositories\UserRepository;
use App\GaelO\Repositories\VisitRepository;

abstract class AbstractTreeService {

    protected string $role;
    protected int $userId;
    protected string $studyName;
    protected PatientRepository $patientRepository;
    protected VisitRepository $visitRepository;
    protected UserRepository $userRepository;

    public function __construct(UserRepository $userRepository, PatientRepository $patientRepository, VisitRepository $visitRepository)
    {
        $this->patientRepository = $patientRepository;
        $this->userRepository = $userRepository;
        $this->visitRepository = $visitRepository;
    }

    public function setUserAndStudy(int $userId, string $studyName)
    {
        $this->userId = $userId;
        $this->studyName = $studyName;
    }

    //SK ICI A REFACTORER LES VISIT ARRAY DEVRAIENT AVOIR LE PATIENT PARRENT DEDANS
    protected function makeTreeFromVisits(array $visitsArray): array
    {

        $responseArray = [];
        $responseArray['visits'] = [];
        $responseArray['patients'] = [];

        $patientIdsArray = array_unique(array_map(function ($visit) {
            return $visit['patient_id'];
        }, $visitsArray));

        $patientsArray = $this->patientRepository->getPatientsFromIdArray($patientIdsArray);
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
