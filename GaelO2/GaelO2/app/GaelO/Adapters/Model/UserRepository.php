<?php

namespace App\GaelO\Adapters\Model;

use App\User;
use App\GaelO\Interfaces\PersistenceInterface;
use App\GaelO\Util;

class UserRepository implements PersistenceInterface {

    public function __construct(){
        $this->user = new User();
    }

    public function create(array $data){
        $model = Util::fillArray($data);
        var_dump($model);
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

    public function getAllUsers() {
        return $this->user->get()->toArray();
    }

    public function getUserByUsername($username){
        $user = $this->user->where('username', $username)->first();
        return $user->toArray();
    }

}

?>