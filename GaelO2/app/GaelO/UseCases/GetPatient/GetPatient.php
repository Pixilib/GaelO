<?php

namespace App\GaelO\UseCases\GetPatient;

use App\GaelO\Constants\Constants;
use App\GaelO\Entities\CenterEntity;
use App\GaelO\Entities\PatientEntity;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\Repositories\PatientRepositoryInterface;
use App\GaelO\Services\AuthorizationService\AuthorizationPatientService;
use App\GaelO\UseCases\GetPatient\GetPatientRequest;
use App\GaelO\UseCases\GetPatient\GetPatientResponse;
use Exception;

class GetPatient
{

    private PatientRepositoryInterface $patientRepositoryInterface;
    private AuthorizationPatientService $authorizationPatientService;

    public function __construct(PatientRepositoryInterface $patientRepositoryInterface, AuthorizationPatientService $authorizationPatientService)
    {
        $this->patientRepositoryInterface = $patientRepositoryInterface;
        $this->authorizationPatientService = $authorizationPatientService;
    }

    public function execute(GetPatientRequest $getPatientRequest, GetPatientResponse $getPatientResponse): void
    {
        try {
            $code = $getPatientRequest->id;

            $this->checkAuthorization($getPatientRequest->currentUserId, $getPatientRequest->role, $code, $getPatientRequest->studyName);
            $dbData = $this->patientRepositoryInterface->getPatientWithCenterDetails($code);

            $responseEntity = PatientEntity::fillFromDBReponseArray($dbData);

            //If Reviewer hide patient's center
            if ($getPatientRequest->role === Constants::ROLE_REVIEWER) {
                $responseEntity->centerCode = null;
            } else {
                $responseEntity->fillCenterDetails(CenterEntity::fillFromDBReponseArray($dbData['center']));
            }

            $getPatientResponse->body = $responseEntity;
            $getPatientResponse->status = 200;
            $getPatientResponse->statusText = 'OK';
        } catch (GaelOException $e) {
            $getPatientResponse->status = $e->statusCode;
            $getPatientResponse->statusText = $e->statusText;
            $getPatientResponse->body = $e->getErrorBody();
        } catch (Exception $e) {
            throw $e;
        }
    }

    private function checkAuthorization(int $currentUserid, string $role, string $patientId, string $studyName)
    {
        $this->authorizationPatientService->setUserId($currentUserid);
        $this->authorizationPatientService->setPatientId($patientId);
        $this->authorizationPatientService->setStudyName($studyName);
        if (!$this->authorizationPatientService->isPatientAllowed($role)) {
            throw new GaelOForbiddenException();
        }
    }
}
