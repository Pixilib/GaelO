<?php

namespace App\GaelO\Repositories;

use App\GaelO\Interfaces\Repositories\UserRepositoryInterface;

use App\Models\CenterUser;
use App\Models\User;
use App\Models\Role;

use App\GaelO\Constants\Constants;
use App\GaelO\Interfaces\Adapters\HashInterface;
use App\GaelO\Util;
use App\Models\Study;

class UserRepository implements UserRepositoryInterface
{

    private User $userModel;
    private Role $rolesModel;
    private CenterUser $centerUserModel;
    private Study $studyModel;
    private HashInterface $hashInterface;

    public function __construct(User $user, Role $roles, CenterUser $centerUser, Study $study, HashInterface $hashInterface)
    {
        $this->userModel = $user;
        $this->rolesModel = $roles;
        $this->centerUserModel = $centerUser;
        $this->studyModel = $study;
        $this->hashInterface = $hashInterface;
    }

    public function find($id): array
    {
        return $this->userModel->findOrFail($id)->toArray();
    }

    public function delete($id): void
    {
        $this->userModel->findOrFail($id)->delete();
    }

    public function getAll($withTrashed): array
    {
        if ($withTrashed) $users = $this->userModel->withTrashed()->get();
        else $users = $this->userModel->get();
        return empty($users) ? [] : $users->toArray();
    }

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
    ): array {

        $user = new User();
        $user->lastname = $lastname;
        $user->firstname = $firstname;
        $user->email = $email;
        $user->phone = $phone;
        $user->administrator = $administrator;
        $user->center_code = $centerCode;
        $user->job = $job;
        $user->orthanc_address = $orthancAdress;
        $user->orthanc_login = $orthancLogin;
        $user->orthanc_password = $orthancPassword;
        $user->password = null;
        $user->creation_date = Util::now();
        $user->save();
        return $user->toArray();
    }

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
        ?String $onboardingVersion,
        bool $resetEmailVerification
    ): void {

        $user = $this->userModel->findOrFail($id);
        $user->lastname = $lastname;
        $user->firstname = $firstname;
        $user->email = $email;
        $user->phone = $phone;
        $user->administrator = $administrator;
        $user->center_code = $centerCode;
        $user->job = $job;
        $user->orthanc_address = $orthancAdress;
        $user->orthanc_login = $orthancLogin;
        $user->orthanc_password = $orthancPassword;
        $user->onboarding_version = $onboardingVersion;
        if ($resetEmailVerification) $user->email_verified_at = null;
        $user->save();
    }

    public function updateUserPassword(int $userId, ?string $passwordCurrent): void
    {
        $user = $this->userModel->findOrFail($userId);
        $user->password = $this->hashInterface->hash($passwordCurrent);
        $user->save();
    }

    public function updateUserAttempts(int $userId, int $attempts): void
    {
        $user = $this->userModel->findOrFail($userId);
        $user->attempts = $attempts;
        $user->save();
    }

    public function resetAttemptsAndUpdateLastConnexion(int $userId): void
    {
        $user = $this->userModel->findOrFail($userId);
        $user->attempts = 0;
        $user->last_connection = Util::now();
        $user->save();
    }

    public function getUserByEmail(String $email, bool $withTrashed = false): array
    {
        if ($withTrashed) {
            $user = $this->userModel->withTrashed()->where('email', $email)->sole();
        } else {
            $user = $this->userModel->where('email', $email)->sole();
        }

        return $user->toArray();
    }

    public function isExistingEmail(String $email): bool
    {
        $user = $this->userModel->withTrashed()->where('email', $email);
        return $user->count() > 0 ? true : false;
    }

    public function reactivateUser(int $id): void
    {
        $this->userModel->withTrashed()->find($id)->restore();
    }

    public function getAdministrators(): array
    {
        $emails = $this->userModel->where('administrator', true)->get();
        return empty($emails) ? [] : $emails->toArray();
    }

    /**
     * Get Emails array of user having an Investigator roles, affiliated (main or affiliated) in centercode
     * and having a particular job
     */
    public function getInvestigatorsOfStudyFromCenter(string $study, int $centerCode, ?string $job): array
    {

        $emails = $this->userModel
            ->with('affiliatedCenters')
            ->whereHas('roles', function ($query) use ($study, $job) {
                if ($job !== null) {
                    $query->where('roles.name', '=', Constants::ROLE_INVESTIGATOR)
                        ->where('roles.study_name', '=', $study)
                        ->where('users.job', '=', $job);
                } else {
                    $query->where('roles.name', '=', Constants::ROLE_INVESTIGATOR)
                        ->where('roles.study_name', '=', $study);
                }
            })
            ->where(function ($query) use ($centerCode) {
                $query->whereHas('affiliatedCenters', function ($query) use ($centerCode) {
                    $query->where('center_code', '=', $centerCode);
                })
                    ->orWhere('users.center_code', '=', $centerCode);
            })
            ->get();

        return empty($emails) ? [] : $emails->toArray();
    }

    public function getUsersByRolesInStudy(string $study, string $role): array
    {

        $users = $this->userModel
            ->whereHas('roles', function ($query) use ($study, $role) {
                $query->where('name', '=', $role)
                    ->where('study_name', '=', $study);
            })
            ->get();

        return empty($users) ? [] : $users->toArray();
    }

    public function getStudiesOfUser(int $userId): array
    {
        $studiesInRole = $this->userModel->findOrFail($userId)->roles()->get()->pluck('study_name')->toArray();
        //2nd call needed to get only non deleted studies
        $studies = $this->studyModel->whereIn('name', $studiesInRole)->get();
        return $studies->count() === 0 ?  [] : $studies->toArray();
    }

    public function getUsersRoles(int $userId, ?array $rolesIn = null): array
    {
        $query = $this->rolesModel->where('user_id', $userId);
        if ($rolesIn != null) {
            $query->whereIn('name', $rolesIn);
        }

        $roles = $query->get(['name', 'study_name']);

        $roles = $roles->groupBy(['study_name'])
            ->map(function ($group) {
                return $group->map(function ($value) {
                    return $value->name;
                });
            });

        return empty($roles) ? [] : $roles->toArray();
    }

    public function getUserRoleInStudy(int $userId, string $studyName, string $roleName): array
    {
        $role = $this->rolesModel
            ->whereHas('user', function ($query) use ($userId) {
                $query->where('id', $userId);
            })
            ->whereHas('study', function ($query) use ($studyName) {
                $query->where('name', $studyName);
            })
            ->with('study')
            ->where('name', $roleName)
            ->sole();

        return $role->toArray();
    }

    public function updateValidatedDocumentationVersion(int $userId, string $studyName, string $roleName, string $version): void
    {
        $role = $this->rolesModel
            ->whereHas('user', function ($query) use ($userId) {
                $query->where('id', $userId);
            })
            ->whereHas('study', function ($query) use ($studyName) {
                $query->where('name', $studyName);
            })
            ->where('name', $roleName)
            ->sole();

        $role->validated_documentation_version = $version;
        $role->save();
    }

    public function getUsersRolesInStudy(int $userId, String $studyName): array
    {
        //Check that called study and user are existing entities (not deleted)
        $roles = $this->rolesModel
            ->whereHas('user', function ($query) use ($userId) {
                $query->where('id', $userId);
            })
            ->whereHas('study', function ($query) use ($studyName) {
                $query->where('name', $studyName);
            })
            ->get();
        return $roles->count() === 0 ? [] : $roles->pluck('name')->toArray();
    }

    public function addUserRoleInStudy(int $userId, String $study, string $role): void
    {

        $user = $this->userModel->findOrFail($userId);
        $insertData = [
            'user_id' => $user['id'],
            'study_name' => $study,
            'name' => $role
        ];
        $user->roles()->insert($insertData);
    }

    public function deleteRoleForUser(int $userId, String $study, String $role): void
    {
        $this->rolesModel->where([
            ['user_id', '=', $userId],
            ['study_name', '=', $study],
            ['name', '=', $role]
        ])->delete();
    }

    public function addAffiliatedCenter(int $userId, int $centerCode): void
    {

        $user = $this->userModel->findOrFail($userId);

        $insertArray = [
            'user_id' => $user['id'],
            'center_code' => $centerCode
        ];

        $this->centerUserModel->insert($insertArray);
    }

    public function deleteAffiliatedCenter(int $userId, int $centerCode): void
    {
        $affiliatedCenter = $this->centerUserModel->where(['user_id' => $userId, 'center_code' => $centerCode])->sole();
        $affiliatedCenter->delete();
    }

    public function getAffiliatedCenter(int $userId): array
    {
        $user = $this->userModel->findOrFail($userId);
        $centers = $user->affiliatedCenters()->get();
        return empty($centers) ? [] : $centers->toArray();
    }

    public function getUserMainCenter(int $userId): array
    {
        return $this->userModel->findOrFail($userId)->mainCenter()->sole()->toArray();
    }

    public function getAllUsersCenters(int $userId): array
    {

        $user = $this->userModel->findOrFail($userId);
        $centers = $user->affiliatedCenters()->get()->pluck('code');
        if (empty($centers)) {
            return [$user['center_code']];
        } else {
            return [...$centers->toArray(), $user['center_code']];
        }
    }

    public function getUsersFromStudy(string $studyName): array
    {
        $users = $this->userModel
            ->whereHas('roles', function ($query) use ($studyName) {
                $query->where('study_name', '=', $studyName);
            })
            ->with(['roles' => function ($query) use ($studyName) {
                $query->where('study_name', '=', $studyName);
            }])
            ->get();
        return empty($users) ? [] : $users->unique('id')->toArray();
    }
}
