<?php

namespace App\GaelO\UseCases\GetPatientsInStudyFromCenters;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\Repositories\PatientRepositoryInterface;
use App\GaelO\UseCases\GetPatientsInStudyFromCenters\GetPatientsInStudyFromCentersRequest;
use App\GaelO\UseCases\GetPatientsInStudyFromCenters\GetPatientsInStudyFromCentersResponse;
use App\GaelO\Entities\PatientEntity;
use App\GaelO\Interfaces\Repositories\StudyRepositoryInterface;
use App\GaelO\Services\AuthorizationService\AuthorizationStudyService;
use Exception;

class GetPatientsInStudyFromCenters
{

    private PatientRepositoryInterface $patientRepositoryInterface;
    private StudyRepositoryInterface $studyRepositoryInterface;
    private AuthorizationStudyService $authorizationStudyService;

    public function __construct(
        PatientRepositoryInterface $patientRepositoryInterface,
        StudyRepositoryInterface $studyRepositoryInterface,
        AuthorizationStudyService $authorizationStudyService
    ) {
        $this->patientRepositoryInterface = $patientRepositoryInterface;
        $this->studyRepositoryInterface = $studyRepositoryInterface;
        $this->authorizationStudyService = $authorizationStudyService;
    }

    public function execute(GetPatientsInStudyFromCentersRequest $getPatientsInStudyFromCentersRequest, GetPatientsInStudyFromCentersResponse $getPatientsInStudyFromCentersResponse): void
    {
        try {

            $studyName = $getPatientsInStudyFromCentersRequest->studyName;
            $centerCodes = $getPatientsInStudyFromCentersRequest->centerCodes;

            //Get Patient from Original Study Name if Ancillary Study
            $studyEntity = $this->studyRepositoryInterface->find($studyName);
            $originalStudyName = $studyEntity->getOriginalStudyName();

            if (!AuthorizationStudyService::isOrginalOrAncillaryStudyOf($studyName, $originalStudyName)) throw new GaelOForbiddenException('Access not granted');

            $this->checkAuthorization($getPatientsInStudyFromCentersRequest->currentUserId, $studyName);

            $responseArray = [];
            $patientsDbEntities = $this->patientRepositoryInterface->getPatientsInStudyInCenters($originalStudyName, $centerCodes, false);

            foreach ($patientsDbEntities as $patientEntity) {
                $patientEntity = PatientEntity::fillFromDBReponseArray($patientEntity);
                $responseArray[] = $patientEntity;
            }

            $getPatientsInStudyFromCentersResponse->body = $responseArray;
            $getPatientsInStudyFromCentersResponse->status = 200;
            $getPatientsInStudyFromCentersResponse->statusText = 'OK';
        } catch (GaelOException $e) {
            $getPatientsInStudyFromCentersResponse->body = $e->getErrorBody();
            $getPatientsInStudyFromCentersResponse->status = $e->statusCode;
            $getPatientsInStudyFromCentersResponse->statusText = $e->statusText;
        } catch (Exception $e) {
            throw $e;
        }
    }

    private function checkAuthorization(int $currentUserId, string $studyName)
    {
        $this->authorizationStudyService->setStudyName($studyName);
        $this->authorizationStudyService->setUserId($currentUserId);
        if (!$this->authorizationStudyService->isAllowedStudy(Constants::ROLE_SUPERVISOR)) {
            throw new GaelOForbiddenException();
        };
    }
}
