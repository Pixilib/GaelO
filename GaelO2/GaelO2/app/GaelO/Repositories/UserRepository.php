<?php

namespace App\GaelO\Repositories;

use App\GaelO\Constants\Constants;
use App\User;
use App\GaelO\Interfaces\PersistenceInterface;
use App\GaelO\Util;
use App\Role;
use DateTime;

class UserRepository implements PersistenceInterface {

    public function __construct(User $user, Role $roles){
        $this->user = $user;
        $this->roles = $roles;
    }

    public function create(array $data){
        Util::fillObject($data, $this->user);
        $this->user->save();
        return $this->user->toArray();
    }

    public function update($id, array $data){
        $model = $this->user->find($id);
        $model = Util::fillObject($data, $model);
        $model->save();
    }

    public function find($id){
        return $this->user->find($id)->toArray();
    }

    public function delete($id) {
        return $this->user->find($id)->delete();
    }

    public function getAll() {
        return $this->user->get()->toArray();
    }

    public function createUser(String $username, String $lastName, String $firstName, String $status,
                                String $email, ?String $phone, bool $administrator, int $centerCode, String $job,
                                ?String $orthancAdress, ?String $orthancLogin, ?String $orthancPassword,
                                String $passwordTemporary, ?String $password, String $creationDate, ?String $lastPasswordUpdate){

        $data= ['username' => $username,
        'lastname' => $lastName,
        'firstname' => $firstName,
        'status' => $status,
        'email' => $email,
        'phone' => $phone,
        'administrator' => $administrator,
        'center_code' => $centerCode,
        'job' => $job,
        'orthanc_address' => $orthancAdress,
        'orthanc_login' => $orthancLogin,
        'orthanc_password' => $orthancPassword,
        'password_temporary'=> $passwordTemporary,
        'password'=> $password,
        'creation_date'=> $creationDate,
        'last_password_update'=> $lastPasswordUpdate];

        return $this->create($data);

    }

    public function updateUser(int $id, String $username, String $lastName, String $firstName, String $status,
                                String $email, ?String $phone, bool $administrator, int $centerCode, String $job,
                                ?String $orthancAdress, ?String $orthancLogin, ?String $orthancPassword,
                                ?String $passwordTemporary, ?String $password, String $creationDate, ?String $lastPasswordUpdate){

        $data= ['username' => $username,
                'lastname' => $lastName,
                'firstname' => $firstName,
                'status' => $status,
                'email' => $email,
                'phone' => $phone,
                'administrator' => $administrator,
                'center_code' => $centerCode,
                'job' => $job,
                'orthanc_address' => $orthancAdress,
                'orthanc_login' => $orthancLogin,
                'orthanc_password' => $orthancPassword,
                'password_temporary'=> $passwordTemporary,
                'password'=> $password,
                'creation_date'=> $creationDate,
                'last_password_update'=> $lastPasswordUpdate];

        return $this->update($id, $data);

    }

    public function getUserByUsername($username){
        $user = $this->user->where('username', $username)->first();
        return $user->toArray();
    }

    public function isExistingUsername($username){
        $user = $this->user->where('username', $username);
        return $user->count() > 0 ? true : false;
    }


    public function isExistingEmail($email){
        $user = $this->user->where('email', $email);
        return $user->count() > 0 ? true : false;
    }

    public function getUserByEmail($email){
        $user = $this->user->where('email', $email)->first();
        return $user->toArray();
    }

    public function getAdministrators(){
        $user = $this->user->where('administrator', true);
        return $user->toArray();
    }

    public function getAdministratorsEmails(){
        $emails = $this->user->where('administrator', true)->pluck('email');
        return $emails->toArray();
    }

    /**
     * Get Emails array of user having an Investigator roles, affiliated (main or affiliated) in centercode
     * and having a particular job
     */
    public function getInvestigatorsStudyFromCenterEmails(string $study, int $centerCode, string $job){

        $emails = $this->user
        ->join('roles', function ($join) {
            $join->on('users.id', '=', 'roles.user_id');
        })->join('center_user', function ($join) {
            $join->on('users.id', '=', 'center_user.user_id');
        })->where(function ($query) use ($study, $job) {
            $query->where('roles.name', '=', Constants::ROLE_INVESTIGATOR)
            ->where('roles.study_name', '=', $study)
            ->where('users.job', '=', $job);
        })->where(function  ($query) use ($centerCode) {
            $query->where('center_user.center_code', '=', $centerCode)
            ->orWhere('users.center_code', '=', $centerCode);
        })
        ->get()->pluck('email');

        return $emails->toArray();
    }

    public function getUsersEmailsByRolesInStudy(string $study, string $role ){

        $emails = $this->user
        ->join('roles', function ($join) {
            $join->on('users.id', '=', 'roles.user_id');
        })->where(function ($query) use ($study, $role) {
            $query->where('roles.name', '=', $role)
            ->where('roles.study_name', '=', $study);
        })->get()->pluck('email');

        return $emails->toArray();

    }

    /**
     * Return users data of users affiliated (main or affiliated) to a center
     */
    public function getUsersAffiliatedToCenter(int $centerCode) : array {

        $users = $this->user
        ->join('center_user', function ($join) {
            $join->on('users.id', '=', 'center_user.user_id');
        })->where(function  ($query) use ($centerCode) {
            $query->where('center_user.center_code', '=', $centerCode)
            ->orWhere('users.center_code', '=', $centerCode);
        })->get();

        return empty($users) ? [] : $users->toArray();
    }

    public function isAlreadyKnownUsernameOrEmail(string $username, string $email){
        $users = $this->user->where('username', $username)->orWhere('email', $email)->get();
        return $users->toArray();
    }

    public function getAllStudiesWithRoleForUser(string $username) : array {
        $user = $this->user->where('username', $username)->first();
        $studies = $user->roles()->join('studies', function ($join) {
            $join->on('roles.study_name', '=', 'studies.name');
        })->distinct('study_name')->get();
        return empty($studies)===true ?  [] : $studies->toArray();
    }

    public function getUsersRoles(int $userId) : array {
        $roles = $this->user->where('id', $userId)->first()->roles()->get(['name', 'study_name']);
        $roles = $roles->groupBy(['study_name'])
                ->map(function ($group) {
                    return $group->map(function ($value) {
                        return $value->name;
                    });
                });

        return empty($roles) ? [] : $roles->toArray();
    }

    public function getUsersRolesInStudy(int $userId, String $study) : array {
        $user = $this->user->where('id', $userId)->first();
        $roles = $user->roles()->where('study_name', $study)->get()->pluck('name');
        return empty($roles) ? [] : $roles->toArray();

    }

    public function addUserRoleInStudy(int $userId, String $study, Array $roles) : void {

        $user = $this->user->where('id', $userId)->first();
        $insertArray = [];

        foreach($roles as $role){
            $insertArray[] =[
                'user_id'=>$user['id'],
                'study_name'=> $study,
                'name'=>$role
            ];
        }

        $user->roles()->insert($insertArray);

    }

    public function deleteRoleForUser(int $userId, String $study, String $role) : void{
        $this->roles->where([
        ['user_id', '=', $userId],
        ['study_name', '=', $study],
        ['name','=', $role]
        ])->delete();
    }
}

?>
