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
        if( !isset($this->userData) ) $this->userData = $this->userRepositoryInterface->find($this->userId);
    }

    private function fillUserCenters(){
        $this->fillUserData();
        if( !isset($this->userCenters) ) $this->userCenters = $this->userRepositoryInterface->getAllUsersCenters($this->userId);
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

    public function isRoleAllowed(string $requestedRole, string $studyName)
    {
        $availableRoles = $this->userRepositoryInterface->getUsersRolesInStudy($this->userId, $studyName);
        return in_array($requestedRole, $availableRoles);
    }

    public function isOneOfRoleAllowed(array $requestedRole, string $studyName)
    {
        $availableRoles = $this->userRepositoryInterface->getUsersRolesInStudy($this->userId, $studyName);
        return sizeof( array_intersect($requestedRole, $availableRoles) ) > 0 ? true : false ;
    }

}
