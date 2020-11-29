<?php

namespace App\GaelO\Services;

use App\GaelO\Constants\Constants;

class AuthorizationReviewService
{

    private AuthorizationVisitService $authorizationVisitService;

    public function __construct(AuthorizationVisitService $authorizationVisitService)
    {

        $this->authorizationVisitService = $authorizationVisitService;
    }

    public function setCurrentUserAndRole(int $userId, string $role)
    {
        $this->requestedRole = $role;
        $this->authorizationVisitService->setCurrentUserAndRole($userId, $role);
    }

    public function setReviewId($reviewId)
    {
        //SK AVOIR LES DATA SUR la VISIT ET injecter visit ID DANS LE MANAGER DE VISIT
        //$this->authorizationVisitService->setVisitId();
    }

    public function isReviewAllowed(): bool
    {
        //SK Si boolean de entity authorize acces a reivew ?
        return $this->authorizationVisitService->isVisitAllowed();
    }
}
