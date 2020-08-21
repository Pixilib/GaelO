<?php

namespace App\GaelO\Repositories;

use App\GaelO\Constants\Constants;
use App\User;
use App\GaelO\Interfaces\PersistenceInterface;
use App\GaelO\Util;

class UserRepository implements PersistenceInterface {

    public function __construct(){
        $this->user = new User();
    }

    public function create(array $data){
        $model = Util::fillObject($data, $this->user);
        $model->save();
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

    public function getUserByUsername($username){
        $user = $this->user->where('username', $username)->first();
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
}

?>
