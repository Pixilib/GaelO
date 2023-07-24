<?php

namespace App\GaelO\Services\AuthorizationService;

use App\GaelO\Constants\Constants;
use App\GaelO\Interfaces\Repositories\ReviewRepositoryInterface;
use App\GaelO\Services\AuthorizationService\AuthorizationVisitService;

class AuthorizationReviewService
{

    private ReviewRepositoryInterface $reviewRepositoryInterface;
    private AuthorizationVisitService $authorizationVisitService;
    private int $userId;
    private int $reviewId;
    private int $reviewOwnerId;
    private array $reviewData;
    private string $reviewStudyName;

    public function __construct(ReviewRepositoryInterface $reviewRepositoryInterface, AuthorizationVisitService $authorizationVisitService)
    {
        $this->reviewRepositoryInterface = $reviewRepositoryInterface;
        $this->authorizationVisitService = $authorizationVisitService;
    }

    public function setUserId(int $userId)
    {
        $this->userId = $userId;
        $this->authorizationVisitService->setUserId($userId);
    }

    public function setReviewId(int $reviewId)
    {
        $this->reviewId = $reviewId;
    }

    private function fillVisitData()
    {
        if (!isset($this->reviewData)) $this->reviewData = $this->reviewRepositoryInterface->find($this->reviewId);
        $this->reviewStudyName = $this->reviewData['study_name'];
        $this->reviewOwnerId = $this->reviewData['user_id'];
    }

    public function isReviewAllowed(string $requestedRole): bool
    {

        $this->fillVisitData();

        $this->authorizationVisitService->setVisitId($this->reviewData['visit_id']);
        $this->authorizationVisitService->setStudyName($this->reviewStudyName);

        if ($requestedRole === Constants::ROLE_REVIEWER) {
            //Role should be allowed and current user be the review owner
            return $this->authorizationVisitService->isVisitAllowed($requestedRole) && ($this->reviewOwnerId === $this->userId);
        } else if ($requestedRole === Constants::ROLE_SUPERVISOR) {
            //Allow Review of the study with supervisor roles
            return $this->authorizationVisitService->isVisitAllowed($requestedRole);
        } else {
            //other roles not allowed to access review data
            return false;
        }
    }
}
