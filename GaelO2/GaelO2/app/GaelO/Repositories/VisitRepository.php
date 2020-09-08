<?php

namespace App\GaelO\Repositories;

use App\Visit;
use App\GaelO\Interfaces\PersistenceInterface;
use App\GaelO\Util;

class VisitRepository implements PersistenceInterface {

    public function __construct(){
        $this->visit = new Visit();
    }

    public function create(array $data){
        $model = Util::fillObject($data, $this->visit);
        $model->save();
    }

    public function update($id, array $data){
        $model = $this->visit->find($id);
        $model = Util::fillObject($data, $model);
        $model->save();
    }

    public function find($id){
        return $this->visit->find($id)->toArray();
    }

    public function delete($id) {
        return $this->visit->find($id)->delete();
    }

    public function getAll() {
        return $this->visit->get()->toArray();
    }

}

?>
