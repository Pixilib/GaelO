<?php

namespace App\GaelO\Repositories;

use App\Patient;
use App\GaelO\Interfaces\PersistenceInterface;
use App\GaelO\Util;

class PatientRepository implements PersistenceInterface {

    public function __construct(Patient $patient){
        $this->patient = $patient;
    }

    public function create(array $data){
        $model = Util::fillObject($data, $this->patient);
        $model->save();
    }

    public function update($code, array $data) : void {
        $model = $this->patient->find($code);
        $model = Util::fillObject($data, $model);
        $model->save();
    }

    public function find($code){
        return $this->patient->where('code', $code)->firstOrFail()->toArray();
    }

    public function delete($code) :void {
        $this->patient->find($code)->delete();
    }

    public function getAll() : array {
        $countries = $this->patient->get();
        return empty($countries) ? []  : $countries->toArray();
    }

}

?>
