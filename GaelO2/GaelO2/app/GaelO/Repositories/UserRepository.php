<?php

namespace App\GaelO\Repositories;

use App\User;
use App\GaelO\Interfaces\PersistenceInterface;
use App\GaelO\Util;

class UserRepository implements PersistenceInterface {

    public function __construct(){
        $this->user = new User();
    }

    public function create(array $data){
        $this->user = Util::fillObject($data, $this->user);
        $this->user->save();
        
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

    public function getAllUsers() {
        return $this->user->get()->toArray();
    }

    public function getUserByUsername($username){
        $user = $this->user->where('username', $username)->first();
        return $user->toArray();
    }

    public function getAdministrators(bool $deactivated =  false ){
        $user = $this->user->where('administrator', true)->where('status', '!=', 'Deactivated');
        return $user->toArray();
    }

    public function getAdministratorsEmails(){
        $emails = $this->user->where('administrator', true)->where('status', '!=', 'Deactivated')->lists('email');
        return $emails->toArray();
    }

}

?>