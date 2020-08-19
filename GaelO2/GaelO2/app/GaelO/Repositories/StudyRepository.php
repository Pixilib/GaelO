<?php

namespace App\GaelO\Repositories;

use App\Study;
use App\GaelO\Interfaces\PersistenceInterface;
use App\GaelO\Util;

class StudyRepository implements PersistenceInterface {

    public function __construct(){
        $this->study = new Study();
    }

    public function create(array $data){
        $model = Util::fillObject($data, $this->study);
        $model->save();
    }

    public function update($id, array $data){
        $model = $this->study->find($id);
        $model = Util::fillObject($data, $model);
        $model->save();
    }

    public function find($id){
        return $this->study->find($id)->toArray();
    }

    public function delete($id) {
        return $this->study->find($id)->delete();
    }

    public function getAll() {
        return $this->study->get()->toArray();
    }

}

?>
