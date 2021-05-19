<?php

namespace App\GaelO\Services;

use App\GaelO\Constants\Constants;
use App\GaelO\Interfaces\Repositories\ReviewRepositoryInterface;

class AuthorizationReviewService
{

    private AuthorizationService $authorizationService;
    private ReviewRepositoryInterface $reviewRepositoryInterface;

    public function __construct(AuthorizationService $authorizationService,
            ReviewRepositoryInterface $reviewRepositoryInterface)
    {

        $this->authorizationService = $authorizationService;
        $this->reviewRepositoryInterface = $reviewRepositoryInterface;
    }

    public function setCurrentUserAndRole(int $userId, string $role)
    {
        $this->currentUserId = $userId;
        $this->requestedRole = $role;
        $this->authorizationService->setCurrentUserAndRole($userId, $role);
    }

    public function setReviewId($reviewId)
    {
        $reviewEntity = $this->reviewRepositoryInterface->find($reviewId);
        $this->reviewStudy = $reviewEntity['study_name'];
        $this->reviewOwner = intval($reviewEntity['user_id']);
    }

    public function isReviewAllowed(): bool
    {

        if ($this->requestedRole === Constants::ROLE_REVIEWER) {
            //Role should be allowed and current user be the review owner
            return $this->authorizationService->isRoleAllowed($this->reviewStudy) && $this->reviewOwner === $this->currentUserId;
        } else if ($this->requestedRole === Constants::ROLE_SUPERVISOR) {
            //Allow Review of the study with supervisor roles
           return $this->authorizationService->isRoleAllowed($this->reviewStudy);
        } else {
            return false;
        }

    }
}
