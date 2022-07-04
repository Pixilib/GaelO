<?php

namespace App\GaelO\UseCases\GetCentersFromStudy;

use App\GaelO\Constants\Constants;
use App\GaelO\Entities\CenterEntity;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\Repositories\CenterRepositoryInterface;
use App\GaelO\Interfaces\Repositories\PatientRepositoryInterface;
use App\GaelO\Interfaces\Repositories\StudyRepositoryInterface;
use App\GaelO\Services\AuthorizationService\AuthorizationStudyService;
use Exception;

class GetCentersFromStudy
{

    private AuthorizationStudyService $authorizationStudyService;
    private PatientRepositoryInterface $patientRepositoryInterface;
    private CenterRepositoryInterface $centerRepositoryInterface;
    private StudyRepositoryInterface $studyRepositoryInterface;


    public function __construct(
        CenterRepositoryInterface $centerRepositoryInterface,
        AuthorizationStudyService $authorizationStudyService,
        PatientRepositoryInterface $patientRepositoryInterface,
        StudyRepositoryInterface $studyRepositoryInterface
    ) {
        $this->centerRepositoryInterface = $centerRepositoryInterface;
        $this->patientRepositoryInterface = $patientRepositoryInterface;
        $this->studyRepositoryInterface = $studyRepositoryInterface;
        $this->authorizationStudyService = $authorizationStudyService;
    }

    public function execute(GetCentersFromStudyRequest $getCentersFromStudyRequest, GetCentersFromStudyResponse $getCentersFromStudyResponse): void
    {
        try {
            $studyName = $getCentersFromStudyRequest->studyName;

            $this->checkAuthorization($getCentersFromStudyRequest->currentUserId, $studyName);

            //Check If study is an ancillary study, as patient should comme from an original study
            $studyEntity = $this->studyRepositoryInterface->find($studyName);
            $originalStudyName = $studyEntity->getOriginalStudyName();

            $patients = $this->patientRepositoryInterface->getPatientsInStudy($originalStudyName);
            $centerCodes = array_column($patients, 'center_code');

            $centers = $this->centerRepositoryInterface->getCentersFromCodeArray($centerCodes);
            $responseArray = [];
            foreach ($centers as $centerEntity) {
                $responseArray[] = CenterEntity::fillFromDBReponseArray($centerEntity);
            }

            $getCentersFromStudyResponse->body = $responseArray;
            $getCentersFromStudyResponse->status = 200;
            $getCentersFromStudyResponse->statusText = 'OK';

        } catch (GaelOException $e) {
            $getCentersFromStudyResponse->status = $e->statusCode;
            $getCentersFromStudyResponse->statusText = $e->statusText;
            $getCentersFromStudyResponse->body = $e->getErrorBody();
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
        };
    }
}
