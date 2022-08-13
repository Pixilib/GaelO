<?php

namespace App\GaelO\UseCases\GetPatientVisit;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\Repositories\VisitRepositoryInterface;
use App\GaelO\Entities\VisitEntity;
use App\GaelO\Exceptions\AbstractGaelOException;
use App\GaelO\Services\AuthorizationService\AuthorizationPatientService;
use Exception;

class GetPatientVisit
{

    private VisitRepositoryInterface $visitRepositoryInterface;
    private AuthorizationPatientService $authorizationPatientService;

    public function __construct(VisitRepositoryInterface $visitRepositoryInterface, AuthorizationPatientService $authorizationPatientService)
    {
        $this->visitRepositoryInterface = $visitRepositoryInterface;
        $this->authorizationPatientService = $authorizationPatientService;
    }

    public function execute(GetPatientVisitRequest $getPatientVisitRequest, GetPatientVisitResponse $getPatientVisitResponse)
    {

        try {

            $role = $getPatientVisitRequest->role;

            $this->checkAuthorization($getPatientVisitRequest->currentUserId, $getPatientVisitRequest->patientId, $getPatientVisitRequest->studyName, $role);
            $visitsArray = $this->visitRepositoryInterface->getAllPatientsVisitsWithReviewStatus($getPatientVisitRequest->patientId, $getPatientVisitRequest->studyName, $getPatientVisitRequest->withTrashed);

            $responseArray = [];
            foreach ($visitsArray as $data) {

                $reviewStatus =  $data['review_status']['review_status'];
                $reviewConclusionValue = $role === Constants::ROLE_SUPERVISOR ? $data['review_status']['review_conclusion_value'] : null;
                $reviewConclusionDate =  $role === Constants::ROLE_SUPERVISOR ? $data['review_status']['review_conclusion_date'] : null;
                $targetLesions =  $role === Constants::ROLE_SUPERVISOR ? $data['review_status']['target_lesions'] : null;

                $visitEntity = VisitEntity::fillFromDBReponseArray($data);
                $visitEntity->setVisitContext($data['visit_type']['visit_group'], $data['visit_type']);
                $visitEntity->setReviewVisitStatus($reviewStatus, $reviewConclusionValue, $reviewConclusionDate, $targetLesions);
                $responseArray[] = $visitEntity;
            }

            $getPatientVisitResponse->body = $responseArray;
            $getPatientVisitResponse->status = 200;
            $getPatientVisitResponse->statusText = 'OK';
        } catch (AbstractGaelOException $e) {

            $getPatientVisitResponse->status = $e->statusCode;
            $getPatientVisitResponse->statusText = $e->statusText;
            $getPatientVisitResponse->body = $e->getErrorBody();
        } catch (Exception $e) {

            throw $e;
        }
    }

    private function checkAuthorization(int $userId, string $patientId, string $studyName, string $role)
    {
        $this->authorizationPatientService->setUserId($userId);
        $this->authorizationPatientService->setPatientId($patientId);
        $this->authorizationPatientService->setStudyName($studyName);
        if (!$this->authorizationPatientService->isPatientAllowed($role)) {
            throw new GaelOForbiddenException();
        }
    }
}
