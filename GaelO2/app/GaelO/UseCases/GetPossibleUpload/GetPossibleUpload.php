<?php

namespace App\GaelO\UseCases\GetPossibleUpload;

use App\GaelO\Constants\Constants;
use App\GaelO\Entities\PatientEntity;
use App\GaelO\Entities\VisitEntity;
use App\GaelO\Exceptions\AbstractGaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\Repositories\UserRepositoryInterface;
use App\GaelO\Interfaces\Repositories\VisitRepositoryInterface;
use App\GaelO\Services\AuthorizationService\AuthorizationStudyService;
use Exception;

class GetPossibleUpload
{

    private VisitRepositoryInterface $visitRepositoryInterface;
    private UserRepositoryInterface $userRepositoryInterface;
    private AuthorizationStudyService $authorizationStudyService;

    public function __construct(AuthorizationStudyService $authorizationStudyService,  VisitRepositoryInterface $visitRepositoryInterface, UserRepositoryInterface $userRepositoryInterface)
    {
        $this->visitRepositoryInterface = $visitRepositoryInterface;
        $this->userRepositoryInterface = $userRepositoryInterface;
        $this->authorizationStudyService = $authorizationStudyService;
    }

    public function execute(GetPossibleUploadRequest $getPossibleUploadRequest, GetPossibleUploadResponse $getPossibleUploadResponse)
    {
        try {

            $this->checkAuthorization($getPossibleUploadRequest->currentUserId, $getPossibleUploadRequest->studyName);

            $centers = $this->userRepositoryInterface->getAllUsersCenters($getPossibleUploadRequest->currentUserId);
            $visitsEntities = $this->visitRepositoryInterface->getImagingVisitsAwaitingUpload($getPossibleUploadRequest->studyName, $centers);

            $answerArray = [];

            foreach ($visitsEntities as $visit) {
                $visitEntity = VisitEntity::fillFromDBReponseArray($visit);
                $visitEntity->setVisitContext($visit['visit_type']['visit_group'], $visit['visit_type']);
                $patientEntity = PatientEntity::fillFromDBReponseArray($visit['patient']);
                $visitEntity->setPatientEntity($patientEntity);
                $answerArray[] = $visitEntity;
            }

            $getPossibleUploadResponse->body = $answerArray;
            $getPossibleUploadResponse->status = 200;
            $getPossibleUploadResponse->statusText = 'OK';
        } catch (AbstractGaelOException $e) {

            $getPossibleUploadResponse->body = $e->getErrorBody();
            $getPossibleUploadResponse->status = $e->statusCode;
            $getPossibleUploadResponse->statusText = $e->statusText;
        } catch (Exception $e) {

            throw $e;
        }
    }

    private function checkAuthorization(int $userId, string $studyName): void
    {
        $this->authorizationStudyService->setUserId($userId);
        $this->authorizationStudyService->setStudyName($studyName);
        if (!$this->authorizationStudyService->isAllowedStudy(Constants::ROLE_INVESTIGATOR)) {
            throw new GaelOForbiddenException();
        }
    }


}
