<?php

namespace App\GaelO\Adapters\Model;

use App\User;
use App\GaelO\Interfaces\PersistenceInterface;

class UserAdapter extends User implements PersistenceInterface {

    public function saveData($id, array $data){
        $model = $this->find($id);
        // call eloquent save method
        $model->save($data);
    }

    public function retrieveData($id){
        return $this->find($id)->toArray();
    }   

}

?>