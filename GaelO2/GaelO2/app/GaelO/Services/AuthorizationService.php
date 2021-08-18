<?php

namespace App\GaelO\Services;

use App\GaelO\Repositories\UserRepository;

class AuthorizationService
{

    public int $userId;
    public ?string $requestedRole;

    public function __construct( UserRepository $userRepository ) {
        $this->userRepository = $userRepository;
    }

    public function setCurrentUserAndRole(int $userId, string $requestedRole = null)
    {
        $this->userId = $userId;
        $this->requestedRole = $requestedRole;
    }

    public function isAdmin(): bool
    {
        $userData = $this->userRepository->find($this->userId);
        return $userData['administrator'];
    }

    public function isRoleAllowed(string $studyName)
    {
        $existingRoles = $this->userRepository->getUsersRolesInStudy($this->userId, $studyName);
        return in_array($this->requestedRole, $existingRoles);
    }

    /**
     * Return if at least one of an array roles is existing for user
     */
    public function isOneOfRolesAllowed(array $roles, string $studyName)
    {
        $existingRoles = $this->userRepository->getUsersRolesInStudy($this->userId, $studyName);
        return sizeof(array_intersect($roles, $existingRoles)) > 0;
    }

    public function isCenterAffiliatedToUser(int $center) : bool {
        $usersCenters = $this->userRepository->getAllUsersCenters($this->userId);
        return in_array($center, $usersCenters);
    }

}
