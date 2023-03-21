<?php

namespace App\GaelO\UseCases\GetPatientFromStudy;

use App\GaelO\Constants\Constants;
use App\GaelO\Entities\CenterEntity;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\Repositories\PatientRepositoryInterface;
use App\GaelO\UseCases\GetPatientFromStudy\GetPatientFromStudyRequest;
use App\GaelO\UseCases\GetPatientFromStudy\GetPatientFromStudyResponse;
use App\GaelO\Entities\PatientEntity;
use App\GaelO\Exceptions\AbstractGaelOException;
use App\GaelO\Interfaces\Repositories\StudyRepositoryInterface;
use App\GaelO\Services\AuthorizationService\AuthorizationStudyService;
use Exception;

class GetPatientFromStudy
{
    private PatientRepositoryInterface $patientRepositoryInterface;
    private StudyRepositoryInterface $studyRepositoryInterface;
    private AuthorizationStudyService $authorizationStudyService;

    public function __construct(
        PatientRepositoryInterface $patientRepositoryInterface,
        AuthorizationStudyService $authorizationStudyService,
        StudyRepositoryInterface $studyRepositoryInterface
    ) {
        $this->patientRepositoryInterface = $patientRepositoryInterface;
        $this->studyRepositoryInterface = $studyRepositoryInterface;
        $this->authorizationStudyService = $authorizationStudyService;
    }

    public function execute(GetPatientFromStudyRequest $patientRequest, GetPatientFromStudyResponse $patientResponse): void
    {
        try {

            $studyName = $patientRequest->studyName;

            $this->checkAuthorization($patientRequest->currentUserId, $studyName);

            //Get Patient from Original Study Name if Ancillary Study
            $studyEntity = $this->studyRepositoryInterface->find($studyName);
            $originalStudyName = $studyEntity->getOriginalStudyName();

            $patientsDbEntities = $this->patientRepositoryInterface->getPatientsInStudy($originalStudyName, true);
            $responseArray = [];
            foreach ($patientsDbEntities as $patientEntity) {
                $patient = PatientEntity::fillFromDBReponseArray($patientEntity);
                $centerEntity = CenterEntity::fillFromDBReponseArray($patientEntity['center']);
                $patient->fillCenterDetails($centerEntity);
                $responseArray[] = $patient;
            }

            $patientResponse->body = $responseArray;
            $patientResponse->status = 200;
            $patientResponse->statusText = 'OK';
        } catch (AbstractGaelOException $e) {
            $patientResponse->body = $e->getErrorBody();
            $patientResponse->status = $e->statusCode;
            $patientResponse->statusText = $e->statusText;
        } catch (Exception $e) {
            throw $e;
        }
    }

    private function checkAuthorization(int $currentUserId, string $studyName)
    {
        $this->authorizationStudyService->setUserId($currentUserId);
        $this->authorizationStudyService->setStudyName($studyName);
        if (!$this->authorizationStudyService->isAllowedStudy(Constants::ROLE_SUPERVISOR)) {
            throw new GaelOForbiddenException();
        }
    }
}
