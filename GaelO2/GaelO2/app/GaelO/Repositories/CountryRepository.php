<?php

namespace App\GaelO\Repositories;

use App\Country;
use App\GaelO\Interfaces\PersistenceInterface;
use App\GaelO\Util;

class CountryRepository implements PersistenceInterface {

    public function __construct(){
        $this->country = new Country();
    }

    public function create(array $data){
        $model = Util::fillObject($data, $this->country);
        $model->save();
    }

    public function update($id, array $data){
        $model = $this->country->find($id);
        $model = Util::fillObject($data, $model);
        $model->save();
    }

    public function find($id){
        return $this->country->find($id)->toArray();
    }

    public function delete($id) {
        return $this->country->find($id)->delete();
    }

    public function getAll() {
        return $this->country->get()->toArray();
    }

}

?>
