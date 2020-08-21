<?php

namespace App\GaelO\Repositories;

use App\GaelO\Constants\Constants;
use App\User;
use App\GaelO\Interfaces\PersistenceInterface;
use App\GaelO\Util;

use Illuminate\Support\Facades\DB;

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

    public function getAdministrators(bool $deactivated =  false){
        $user = $this->user->where('administrator', true);
        return $user->toArray();
    }

    public function getAdministratorsEmails(){
        $emails = $this->user->where('administrator', true)->pluck('email');
        return $emails->toArray();
    }

    public function getInvestigatorsStudyFromCenterEmails($study, $centerCode, $job){

        $emails = DB::table('users')
        ->join('roles', function ($join) {
            $join->on('users.id', '=', 'roles.user_id');
        })->join('affiliated_centers', function ($join) {
            $join->on('users.id', '=', 'affiliated_centers.user_id');
        })->where(function ($query) use ($study, $job) {
            $query->where('roles.role_name', '=', Constants::ROLE_INVESTIGATOR)
            ->where('roles.study_name', '=', $study)
            ->where('users.job', '=', $job);
        })->where(function  ($query) use ($centerCode) {
            $query->where('affiliated_centers.center_code', '=', $centerCode)
            ->orWhere('users.center_code', '=', $centerCode);
        })
        ->get()->pluck('email');

/*
        $emails = DB::table('users')
            ->with('roles')
            ->with('affiliated_centers')
            ->select('users.email')
            ->where('roles.role_name', '=', Constants::ROLE_INVESTIGATOR)
            ->where('roles.study_name', '=', $study)
            ->where(function  ($query) use ($centerCode) {
                $query->where('affiliated_centers.center_code', '=', $centerCode)
                ->orWhere('users.center_code', '=', $centerCode);
            })
            ->when($job, function ($query, $job) {
                return $query->where('users.job', '=', $job);
            })
            ->pluck('email');*/
        return $emails->toArray();
    }
}

?>
