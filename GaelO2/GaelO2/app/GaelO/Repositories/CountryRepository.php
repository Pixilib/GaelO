<?php

namespace App\GaelO\Repositories;

use App\Model\Country;
use App\GaelO\Interfaces\PersistenceInterface;
use App\GaelO\Util;

class CountryRepository implements PersistenceInterface {

    public function __construct(Country $country){
        $this->country = $country;
    }

    public function create(array $data){
        $country = new Country();
        $model = Util::fillObject($data, $country);
        $model->save();
    }

    public function update($code, array $data) : void {
        $model = $this->country->find($code);
        $model = Util::fillObject($data, $model);
        $model->save();
    }

    public function find($code){
        return $this->country->findOrFail($code)->toArray();
    }

    public function delete($code) :void {
        $this->country->find($code)->delete();
    }

    public function getAll() : array {
        $countries = $this->country->get();
        return empty($countries) ? []  : $countries->toArray();
    }

}

?>
