<?php

namespace App\GaelO\UseCases\GetVisit;

use App\GaelO\Constants\Constants;
use App\GaelO\Entities\UserEntity;
use App\GaelO\Entities\VisitEntity;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\Repositories\VisitRepositoryInterface;
use App\GaelO\Services\AuthorizationService\AuthorizationVisitService;
use Exception;

class GetVisit
{

    private VisitRepositoryInterface $visitRepositoryInterface;
    private AuthorizationVisitService $authorizationVisitService;

    public function __construct(VisitRepositoryInterface $visitRepositoryInterface, AuthorizationVisitService $authorizationVisitService)
    {
        $this->visitRepositoryInterface = $visitRepositoryInterface;
        $this->authorizationVisitService = $authorizationVisitService;
    }

    public function execute(GetVisitRequest $getVisitRequest, GetVisitResponse $getVisitResponse)
    {

        try {
            $visitId = $getVisitRequest->visitId;
            $studyName = $getVisitRequest->studyName;
            $role = $getVisitRequest->role;

            $this->checkAuthorization($visitId, $getVisitRequest->currentUserId, $getVisitRequest->role, $studyName);

            $visitEntity = $this->visitRepositoryInterface->getVisitWithContextAndReviewStatus($visitId, $studyName);

            $responseEntity = VisitEntity::fillFromDBReponseArray($visitEntity);
            $responseEntity->setVisitContext(
                $visitEntity['visit_type']['visit_group'],
                $visitEntity['visit_type']
            );

            $reviewStatus = $visitEntity['review_status']['review_status'];
            //Allow visit conclusion and date only for supervisor
            $reviewConclusionValue = (in_array($role, [Constants::ROLE_SUPERVISOR])) ? $visitEntity['review_status']['review_conclusion_value'] : null;
            $reviewConclusionDate =  (in_array($role, [Constants::ROLE_SUPERVISOR])) ? $visitEntity['review_status']['review_conclusion_date'] : null;
            //Target lesions are allowed also for reviewer
            $targetLesions =  (in_array($role, [Constants::ROLE_REVIEWER, Constants::ROLE_SUPERVISOR])) ? $visitEntity['review_status']['target_lesions'] : null;

            $responseEntity->setReviewVisitStatus($reviewStatus, $reviewConclusionValue, $reviewConclusionDate, $targetLesions);
            $responseEntity->setCreatorDetails(UserEntity::fillOnlyUserIdentification($visitEntity['creator']));

            $getVisitResponse->body = $responseEntity;
            $getVisitResponse->status = 200;
            $getVisitResponse->statusText = 'OK';
        } catch (GaelOException $e) {
            $getVisitResponse->body = $e->getErrorBody();
            $getVisitResponse->status  = $e->statusCode;
            $getVisitResponse->statusText = $e->statusText;
        } catch (Exception $e) {
            throw $e;
        }
    }

    private function checkAuthorization(int $visitId, int $userId, string $role, string $studyName)
    {
        $this->authorizationVisitService->setUserId($userId);
        $this->authorizationVisitService->setStudyName($studyName);
        $this->authorizationVisitService->setVisitId($visitId);

        if (!$this->authorizationVisitService->isVisitAllowed($role)) {
            throw new GaelOForbiddenException();
        }
    }
}
