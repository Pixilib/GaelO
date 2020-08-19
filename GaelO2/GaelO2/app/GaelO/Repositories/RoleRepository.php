<?php

namespace App\GaelO\Repositories;

use App\Role;
use App\GaelO\Interfaces\PersistenceInterface;
use App\GaelO\Util;

class RoleRepository implements PersistenceInterface {

    public function __construct(){
        $this->role = new Role();
    }

    public function create(array $data){
        $model = Util::fillObject($data, $this->role);
        $model->save();
    }

    public function update($id, array $data){
        $model = $this->role->find($id);
        $model = Util::fillObject($data, $model);
        $model->save();
    }

    public function find($id){
        return $this->role->find($id)->toArray();
    }

    public function delete($id) {
        return $this->role->find($id)->delete();
    }

    public function getAll() {
        return $this->role->get()->toArray();
    }

}

?>
