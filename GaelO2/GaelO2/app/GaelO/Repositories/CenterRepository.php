<?php

namespace App\GaelO\Repositories;

use App\Center;
use App\GaelO\Interfaces\PersistenceInterface;
use App\GaelO\Util;

class CenterRepository implements PersistenceInterface {

    public function __construct(){
        $this->center = new Center();
    }

    public function create(array $data){
        $model = Util::fillObject($data, $this->center);
        $model->save();
    }

    public function createCenter(int $code, string $name, string $countryCode){
        $data = [
            'code' => $code,
            'name' => $name,
            'country_code' => $countryCode
        ];

        $this->create($data);
    }

    public function update($id, array $data){
        $model = $this->center->find($id);
        $model = Util::fillObject($data, $model);
        $model->save();
    }

    public function find($id){
        return $this->center->find($id)->toArray();
    }

    public function delete($id) {
        return $this->center->find($id)->delete();
    }

    public function getAll() {
        return $this->center->get()->toArray();
    }

}

?>
