<?php

namespace App\GaelO\Services\AuthorizationService;

use App\GaelO\Interfaces\Repositories\UserRepositoryInterface;

class AuthorizationUserService
{
    private int $userId;
    private array $userData;
    private array $userCenters;

    private UserRepositoryInterface $userRepositoryInterface;

    public function __construct( UserRepositoryInterface $userRepositoryInterface ) {
        $this->userRepositoryInterface = $userRepositoryInterface;
    }

    private function fillUserData(){
        if($this->userData == null ) $this->userData = $this->userRepositoryInterface->find($this->userId);
    }

    private function fillUserCenters(){
        $this->fillUserData();
        if(!$this->userCenters == null ) $this->userCenters = $this->userRepositoryInterface->getAllUsersCenters($this->userId);
    }

    public function setUserId(int $userId)
    {
        $this->userId = $userId;
    }

    public function isAdmin(): bool
    {
        $this->fillUserData();
        return $this->userData['administrator'];
    }

    public function isCenterAffiliatedToUser(int $center) : bool {
        $this->fillUserCenters();
        return in_array($center, $this->userCenters);
    }

    public function isRoleAllowed(string|array $requestedRole, string $studyName)
    {
        $existingRoles = $this->userRepositoryInterface->getUsersRolesInStudy($this->userId, $studyName);
        return in_array($requestedRole, $existingRoles);
    }

}
