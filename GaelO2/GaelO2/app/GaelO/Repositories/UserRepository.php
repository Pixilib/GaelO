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

class UserRepository implements UserRepositoryInterface {

    private User $user;
    private Role $roles;
    private CenterUser $centerUser;
    private Study $study;
    private HashInterface $hashInterface;

    public function __construct(User $user, Role $roles, CenterUser $centerUser, Study $study, HashInterface $hashInterface){
        $this->user = $user;
        $this->roles = $roles;
        $this->centerUser = $centerUser;
        $this->study = $study;
        $this->hashInterface = $hashInterface;
    }

    public function find($id) : array {
        return $this->user->findOrFail($id)->toArray();
    }

    public function delete($id) : void {
        $this->user->findOrFail($id)->delete();
    }

    public function getAll() : array {
        $users = $this->user->withTrashed()->get();
        return empty($users) ? [] : $users->toArray();
    }

    public function createUser( String $username, String $lastname, String $firstname, String $status,
                                String $email, ?String $phone, bool $administrator, int $centerCode, String $job,
                                ?String $orthancAdress, ?String $orthancLogin, ?String $orthancPassword,
                                String $passwordTemporary ) : array {

        $user = new User();
        $user->username = $username;
        $user->lastname = $lastname;
        $user->firstname = $firstname;
        $user->status = $status;
        $user->email = $email;
        $user->phone = $phone;
        $user->administrator = $administrator;
        $user->center_code = $centerCode;
        $user->job = $job;
        $user->orthanc_address = $orthancAdress;
        $user->orthanc_login = $orthancLogin;
        $user->orthanc_password = $orthancPassword;
        $user->password_temporary = $passwordTemporary;
        $user->password = null;
        $user->creation_date = Util::now();
        $user->last_password_update = null;
        $user->save();
        return $user->toArray();

    }

    public function updateUser(int $id, String $username, ?String $lastname, ?String $firstname, String $status,
                                String $email, ?String $phone, bool $administrator, int $centerCode, String $job,
                                ?String $orthancAdress, ?String $orthancLogin, ?String $orthancPassword,
                                ?String $passwordTemporary) : void {

        $user = $this->user->findOrFail($id);
        $user->username = $username;
        $user->lastname = $lastname;
        $user->firstname = $firstname;
        $user->status = $status;
        $user->email = $email;
        $user->phone = $phone;
        $user->administrator = $administrator;
        $user->center_code = $centerCode;
        $user->job = $job;
        $user->orthanc_address = $orthancAdress;
        $user->orthanc_login = $orthancLogin;
        $user->orthanc_password = $orthancPassword;
        $user->password_temporary = $passwordTemporary ? $this->hashInterface->hash($passwordTemporary) : null;
        $user->save();

    }

    public function updateUserPassword(int $userId, ?string $passwordCurrent ) : void {
        $user = $this->user->findOrFail($userId);
        $user->password_previous2 = $user->password_previous1;
        $user->password_previous1 = $user->password;
        $user->password = $this->hashInterface->hash($passwordCurrent);
        $user->last_password_update = Util::now();
        $user->save();
    }

    public function updateUserTemporaryPassword(int $userId, ?string $passwordTemporary ) : void {
        $user = $this->user->findOrFail($userId);
        $user->password_temporary = $this->hashInterface->hash($passwordTemporary);
        $user->last_password_update = Util::now();
        $user->save();
    }

    public function updateUserAttempts(int $userId, int $attempts ) : void {
        $user = $this->user->findOrFail($userId);
        $user->attempts = $attempts;
        $user->save();
    }

    public function updateUserStatus(int $userId, string $status ) : void {
        $user = $this->user->findOrFail($userId);
        $user->status = $status;
        $user->save();
    }

    public function resetAttemptsAndUpdateLastConnexion( int $userId ) : void {
        $user = $this->user->findOrFail($userId);
        $user->attempts = 0;
        $user->last_connection = Util::now();
        $user->save();
    }

    public function getUserByUsername(String $username, bool $withTrashed = false) : array {
        if($withTrashed){
            $user = $this->user->withTrashed()->where('username', $username)->sole();
        }else{
            $user = $this->user->where('username', $username)->sole();
        }

        return $user->toArray();
    }

    public function isExistingUsername(String $username) : bool {
        $user = $this->user->withTrashed()->where('username', $username);
        return $user->count() > 0 ? true : false;
    }


    public function isExistingEmail(String $email) : bool {
        $user = $this->user->withTrashed()->where('email', $email);
        return $user->count() > 0 ? true : false;
    }

    public function reactivateUser(int $id) : void {
        $this->user->withTrashed()->find($id)->restore();
    }

    public function getAdministratorsEmails() : array {
        $emails = $this->user->where([['administrator', true], ['status', 'Activated']])->get();
        return empty($emails) ? [] : $emails->pluck('email')->toArray();
    }

    /**
     * Get Emails array of user having an Investigator roles, affiliated (main or affiliated) in centercode
     * and having a particular job
     */
    public function getInvestigatorsStudyFromCenterEmails(string $study, int $centerCode, ?string $job) : array {

        $emails = $this->user
        ->where('status', 'Activated')
        ->with('affiliatedCenters')
        ->whereHas('roles', function ($query) use ($study, $job) {
            if($job !== null){
                $query->where('roles.name', '=', Constants::ROLE_INVESTIGATOR)
                ->where('roles.study_name', '=', $study)
                ->where('users.job', '=', $job);
            }else{
                $query->where('roles.name', '=', Constants::ROLE_INVESTIGATOR)
                ->where('roles.study_name', '=', $study);
            }
        })
        ->where(function  ($query) use ($centerCode) {
            $query->whereHas('affiliatedCenters', function ($query) use ($centerCode) {
                $query->where('center_code', '=', $centerCode);
            })
            ->orWhere('users.center_code', '=', $centerCode);
        })
        ->get();

        return empty($emails) ? [] : $emails->pluck('email')->toArray();
    }

    public function getUsersByRolesInStudy(string $study, string $role ) : array {

        $users = $this->user
        ->where('status', 'Activated')
        ->whereHas('roles', function ($query) use ($study, $role) {
            $query->where('name', '=', $role)
            ->where('study_name', '=', $study);
        })
        ->get();

        return empty($users) ? [] : $users->toArray();

    }

    public function getUsersEmailsByRolesInStudy(string $study, string $role ) : array {

        $users = $this->getUsersByRolesInStudy($study, $role);
        $emails = array_map(function($user) {
            return $user['email'];
        }, $users);

        return $emails;

    }

    public function getStudiesOfUser(int $userId) : array {
        $studiesInRole = $this->user->findOrFail($userId)->roles()->get()->pluck('study_name')->toArray();
        //2nd call needed to get only non deleted studies
        $studies = $this->study->whereIn('name', $studiesInRole)->get();
        return $studies->count() === 0 ?  [] : $studies->toArray();
    }

    public function getUsersRoles(int $userId) : array {
        $roles = $this->user->findOrFail($userId)->roles()->get(['name', 'study_name']);
        $roles = $roles->groupBy(['study_name'])
                ->map(function ($group) {
                    return $group->map(function ($value) {
                        return $value->name;
                    });
                });

        return empty($roles) ? [] : $roles->toArray();
    }

    public function getUsersRolesInStudy(int $userId, String $studyName) : array {
        //Check that called study and user are existing entities (not deleted)
        $study = $this->study->findOrFail($studyName);
        $user = $this->user->findOrFail($userId);
        $roles = $this->roles
            ->where('user_id', $user->id)
            ->where('study_name', $study->name)
            ->get();
        return $roles->count() === 0 ? [] : $roles->pluck('name')->toArray();
    }

    public function addUserRoleInStudy(int $userId, String $study, string $role) : void {

        $user = $this->user->findOrFail($userId);
        $insertData =[
            'user_id'=>$user['id'],
            'study_name'=> $study,
            'name'=>$role
        ];
        $user->roles()->insert($insertData);

    }

    public function deleteRoleForUser(int $userId, String $study, String $role) : void {
        $this->roles->where([
            ['user_id', '=', $userId],
            ['study_name', '=', $study],
            ['name','=', $role]
            ])->delete();
    }

    public function addAffiliatedCenter(int $userId, int $centerCode) : void {

        $user = $this->user->findOrFail($userId);

        $insertArray = [
            'user_id'=>$user['id'],
            'center_code'=> $centerCode
        ];

        $this->centerUser->insert($insertArray);

    }

    public function deleteAffiliatedCenter(int $userId, int $centerCode) : void {
        $affiliatedCenter=$this->centerUser->where( ['user_id'=> $userId,'center_code'=>$centerCode] )->sole();
        $affiliatedCenter->delete();
    }

    public function getAffiliatedCenter(int $userId) : array {
        $user = $this->user->findOrFail($userId);
        $centers = $user->affiliatedCenters()->get();
        return empty($centers) ? [] : $centers->toArray();
    }

    public function getAllUsersCenters(int $userId) : array {

        $user = $this->user->findOrFail($userId);
        $centers = $user->affiliatedCenters()->get()->pluck('code');
        if(empty($centers)){
            return [ $user['center_code'] ];
        }else {
            return [...$centers->toArray(), $user['center_code']];
        }


    }

    public function getUsersFromStudy(string $studyName) : array {

        $users = $this->user
        ->whereHas('roles', function ($query) use ($studyName) {
            $query->where('study_name', '=', $studyName);
        })
        ->with('roles')
        ->get();
        return empty($users) ? [] : $users->unique('id')->toArray();
    }
}
