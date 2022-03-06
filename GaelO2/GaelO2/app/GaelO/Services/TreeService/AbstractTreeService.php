<?php

namespace App\GaelO\Services\TreeService;

use App\GaelO\Entities\StudyEntity;
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

    protected function makePatientDetails( array $patientIdArray) : array {

        $patientsArray = $this->patientRepositoryInterface->getPatientsFromIdArray($patientIdArray);

        $centersArray = $this->centerRepositoryInterface->getCentersFromCodeArray(
            array_map(function ($patient) { return $patient['center_code']; }, $patientsArray)
        );

        $patientArray = [];

        foreach ($patientsArray as $patientEntity) {
            $centerIndex = array_search($patientEntity['center_code'], array_column($centersArray, 'code'));
            $patientArray[ $patientEntity['id'] ] = [
                'code' => $patientEntity['code'],
                'centerName' => $centersArray[$centerIndex]['name'],
                'centerCode' => $patientEntity['center_code']
            ];
        }

        return $patientArray;

    }

    protected function makeVisitDetails(array $visitsArray): array
    {
        $visitArray=[];
        foreach ($visitsArray as $visitObject) {
            $visitsFormattedData = (array) TreeItem::createItem($visitObject);
            $visitArray[] = $visitsFormattedData;
        }

        return $visitArray;
    }

    protected function formatResponse(array $visitArray) : array{
        $patientIdArray = array_map(function($visit){
            return $visit['patient_id'];
        }, $visitArray);

        return[
            'patients'=> $this->makePatientDetails($patientIdArray),
            'visits' => $this->makeVisitDetails($visitArray)
        ];
    }

    public abstract function buildTree(): array;
}
