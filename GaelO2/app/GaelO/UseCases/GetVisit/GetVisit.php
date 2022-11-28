<?php

namespace App\GaelO\UseCases\GetVisit;

use App\GaelO\Constants\Constants;
use App\GaelO\Entities\UserEntity;
use App\GaelO\Entities\VisitEntity;
use App\GaelO\Exceptions\AbstractGaelOException;
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

            $visitContext = $this->visitRepositoryInterface->getVisitWithContextAndReviewStatus($visitId, $studyName);
            $this->checkAuthorization($visitId, $getVisitRequest->currentUserId, $getVisitRequest->role, $studyName, $visitContext);
            
            $responseEntity = VisitEntity::fillFromDBReponseArray($visitContext);
            $responseEntity->setVisitContext(
                $visitContext['visit_type']['visit_group'],
                $visitContext['visit_type']
            );

            $reviewStatus = $visitContext['review_status']['review_status'];
            //Allow visit conclusion and date only for supervisor
            $reviewConclusionValue = (in_array($role, [Constants::ROLE_SUPERVISOR])) ? $visitContext['review_status']['review_conclusion_value'] : null;
            $reviewConclusionDate =  (in_array($role, [Constants::ROLE_SUPERVISOR])) ? $visitContext['review_status']['review_conclusion_date'] : null;
            //Target lesions are allowed also for reviewer
            $targetLesions =  (in_array($role, [Constants::ROLE_REVIEWER, Constants::ROLE_SUPERVISOR])) ? $visitContext['review_status']['target_lesions'] : null;
            $reviewAvailable =  (in_array($role, [Constants::ROLE_REVIEWER, Constants::ROLE_SUPERVISOR])) ? $visitContext['review_status']['review_available'] : null;

            $responseEntity->setReviewVisitStatus($reviewStatus, $reviewConclusionValue, $reviewConclusionDate, $targetLesions, $reviewAvailable);
            $responseEntity->setCreatorDetails(UserEntity::fillOnlyUserIdentification($visitContext['creator']));

            $getVisitResponse->body = $responseEntity;
            $getVisitResponse->status = 200;
            $getVisitResponse->statusText = 'OK';
        } catch (AbstractGaelOException $e) {
            $getVisitResponse->body = $e->getErrorBody();
            $getVisitResponse->status  = $e->statusCode;
            $getVisitResponse->statusText = $e->statusText;
        } catch (Exception $e) {
            throw $e;
        }
    }

    private function checkAuthorization(int $visitId, int $userId, string $role, string $studyName, array $visitContext)
    {
        $this->authorizationVisitService->setUserId($userId);
        $this->authorizationVisitService->setStudyName($studyName);
        $this->authorizationVisitService->setVisitId($visitId);
        $this->authorizationVisitService->setVisitContext($visitContext);

        if (!$this->authorizationVisitService->isVisitAllowed($role)) {
            throw new GaelOForbiddenException();
        }
    }
}
