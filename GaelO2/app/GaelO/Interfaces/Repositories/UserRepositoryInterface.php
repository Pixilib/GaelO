<?php

namespace App\GaelO\Interfaces\Repositories;

interface UserRepositoryInterface
{

    public function find(int $id): array;

    public function getAll(bool $withTrashed): array;

    public function delete($id): void;

    public function createUser(
        String $lastname,
        String $firstname,
        String $email,
        ?String $phone,
        bool $administrator,
        int $centerCode,
        String $job,
        ?String $orthancAdress,
        ?String $orthancLogin,
        ?String $orthancPassword
    ): array;

    public function updateUser(
        int $id,
        ?String $lastname,
        ?String $firstname,
        String $email,
        ?String $phone,
        bool $administrator,
        int $centerCode,
        String $job,
        ?String $orthancAdress,
        ?String $orthancLogin,
        ?String $orthancPassword,
        ?string $onboardingVersion,
        bool $resetEmailVerify
    ): void;

    public function updateUserPassword(int $userId, ?string $passwordCurrent): void;

    public function updateUserAttempts(int $userId, int $attempts): void;

    public function resetAttemptsAndUpdateLastConnexion(int $userId): void;

    public function getUserByEmail(String $email, bool $withTrashed = false): array;

    public function isExistingEmail(String $email): bool;

    public function reactivateUser(int $id): void;

    public function getAdministratorsEmails(): array;

    public function getInvestigatorsEmailsFromStudyFromCenter(string $study, int $centerCode, ?string $job): array;

    public function getUsersByRolesInStudy(string $study, string $role): array;

    public function getUsersEmailsByRolesInStudy(string $study, string $role): array;

    public function getStudiesOfUser(int $userId): array;

    public function getUsersRoles(int $userId, ?array $rolesIn = null): array;

    public function updateValidatedDocumentationVersion(int $userId, string $studyName, string $roleName, string $version): void;

    public function getUserRoleInStudy(int $userId, string $studyName, string $role): array;

    public function getUsersRolesInStudy(int $userId, String $study): array;

    public function addUserRoleInStudy(int $userId, String $study, string $role): void;

    public function deleteRoleForUser(int $userId, String $study, String $role): void;

    public function getUserMainCenter(int $userId): array;

    public function addAffiliatedCenter(int $userId, int $centerCode): void;

    public function deleteAffiliatedCenter(int $userId, int $centerCode): void;

    public function getAffiliatedCenter(int $userId): array;

    public function getAllUsersCenters(int $userId): array;

    public function getUsersFromStudy(string $studyName): array;
}
