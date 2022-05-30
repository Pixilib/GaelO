<?php

namespace App\GaelO\UseCases\GetCreatableVisits;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\Repositories\PatientRepositoryInterface;
use App\GaelO\Services\AuthorizationService\AuthorizationPatientService;
use App\GaelO\Services\GaelOStudiesService\AbstractGaelOStudy;
use Exception;

class GetCreatableVisits
{

    private AuthorizationPatientService $authorizationPatientService;
    private PatientRepositoryInterface $patientRepositoryInterface;

    public function __construct(AuthorizationPatientService $authorizationPatientService, PatientRepositoryInterface $patientRepositoryInterface)
    {
        $this->authorizationPatientService = $authorizationPatientService;
        $this->patientRepositoryInterface = $patientRepositoryInterface;
    }

    public function execute(GetCreatableVisitsRequest $getCreatableVisitsRequest, GetCreatableVisitsResponse $getCreatableVisitsResponse)
    {

        try {
            $patientId = $getCreatableVisitsRequest->patientId;
            $patientEntity = $this->patientRepositoryInterface->find($patientId);
            $studyName = $patientEntity['study_name'];

            $this->checkAuthorization($getCreatableVisitsRequest->currentUserId, $patientId, $studyName);

            //Get Visit To Create from specific Study Object
            $studyObject = AbstractGaelOStudy::getSpecificStudyObject($studyName);
            $visitToCreate = $studyObject->getCreatableVisitCalculator()->getAvailableVisitToCreate($patientEntity);

            $getCreatableVisitsResponse->status = 200;
            $getCreatableVisitsResponse->statusText = 'OK';
            $getCreatableVisitsResponse->body = $visitToCreate;
        } catch (GaelOException $e) {
            $getCreatableVisitsResponse->status = $e->statusCode;
            $getCreatableVisitsResponse->statusText = $e->statusText;
            $getCreatableVisitsResponse->body = $e->getErrorBody();
        } catch (Exception $e) {
            throw $e;
        }
    }

    private function checkAuthorization(int $userId, string $patientId, string $studyName)
    {
        $this->authorizationPatientService->setUserId($userId);
        $this->authorizationPatientService->setStudyName($studyName);
        $this->authorizationPatientService->setPatientId($patientId);
        if (!$this->authorizationPatientService->isPatientAllowed(Constants::ROLE_INVESTIGATOR)) {
            throw new GaelOForbiddenException();
        }
    }
}
