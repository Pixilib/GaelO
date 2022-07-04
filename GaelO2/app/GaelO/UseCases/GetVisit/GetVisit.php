<?php

namespace App\GaelO\UseCases\GetVisit;

use App\GaelO\Entities\UserEntity;
use App\GaelO\Entities\VisitEntity;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\Repositories\ReviewStatusRepositoryInterface;
use App\GaelO\Interfaces\Repositories\UserRepositoryInterface;
use App\GaelO\Interfaces\Repositories\VisitRepositoryInterface;
use App\GaelO\Services\AuthorizationService\AuthorizationVisitService;
use Exception;

class GetVisit
{

    private VisitRepositoryInterface $visitRepositoryInterface;
    private ReviewStatusRepositoryInterface $reviewStatusRepositoryInterface;
    private AuthorizationVisitService $authorizationVisitService;
    private UserRepositoryInterface $userRepositoryInterface;

    public function __construct(VisitRepositoryInterface $visitRepositoryInterface, ReviewStatusRepositoryInterface $reviewStatusRepositoryInterface, UserRepositoryInterface $userRepositoryInterface, AuthorizationVisitService $authorizationVisitService)
    {
        $this->visitRepositoryInterface = $visitRepositoryInterface;
        $this->authorizationVisitService = $authorizationVisitService;
        $this->reviewStatusRepositoryInterface = $reviewStatusRepositoryInterface;
        $this->userRepositoryInterface =  $userRepositoryInterface;
    }

    public function execute(GetVisitRequest $getVisitRequest, GetVisitResponse $getVisitResponse)
    {

        try {
            $visitId = $getVisitRequest->visitId;
            $studyName = $getVisitRequest->studyName;

            $this->checkAuthorization($visitId, $getVisitRequest->currentUserId, $getVisitRequest->role, $studyName);

            $visitEntity = $this->visitRepositoryInterface->getVisitWithContextAndReviewStatus($visitId, $studyName);

            $responseEntity = VisitEntity::fillFromDBReponseArray($visitEntity);
            $responseEntity->setVisitContext(
                $visitEntity['visit_type']['visit_group'],
                $visitEntity['visit_type']
            );
            $responseEntity->setReviewVisitStatus($visitEntity['review_status']['review_status'], $visitEntity['review_status']['review_conclusion_value'], $visitEntity['review_status']['review_conclusion_date'], $visitEntity['review_status']['target_lesions']);
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
