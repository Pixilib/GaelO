<?php

namespace App\GaelO\Adapters\Model;

use App\User;
use App\GaelO\Interfaces\PersistenceInterface;
use App\GaelO\Util;

class UserAdapter implements PersistenceInterface {

    public function __construct(){
        $this->user = new User();
    }

    public function createUser(array $data){
        $model = Util::fillArray($data);
        var_dump($model);
        $model->save();
    }

    public function updateUser($id, array $data){
        $model = $this->user->find($id);

        $model = Util::fillObject($data, $model);
        
        $model->save();
    }

    public function retrieveData($id){
        return $this->user->find($id)->toArray();
    }   

    public function getAllUsers() {
        return $this->user->toArray();
    }

    public function getUserByUsername($username){
        $user = $this->user->where('username', $username)->first();
        return $user->toArray();
    }

}

?>