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

    public function update($name, array $data){
        $model = $this->study->find($name);
        $model = Util::fillObject($data, $model);
        $model->save();
    }

    public function find($name){
        return $this->study->find($name)->toArray();
    }

    public function delete($name) {
        return $this->study->find($name)->delete();
    }

    public function getAll() {
        return $this->study->get()->toArray();
    }

    public function addStudy($name, $patientCodePreffix) : void {

        $data = [
            'name'=>$name,
            'patient_code_prefix'=>$patientCodePreffix
        ];

        $this->create($data);


    }

}

?>
