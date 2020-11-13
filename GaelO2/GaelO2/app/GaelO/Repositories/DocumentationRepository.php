<?php

namespace App\GaelO\Repositories;

use App\Documentation;
use App\GaelO\Interfaces\PersistenceInterface;
use App\GaelO\Util;

class DocumentationRepository implements PersistenceInterface {

    public function __construct(Documentation $documentation){
        $this->documentation = $documentation;
    }

    public function create(array $data){
        $documentation = new Documentation();
        $model = Util::fillObject($data, $documentation);
        $model->save();
    }

    public function update($code, array $data) : void{
        $model = $this->documentation->find($code);
        $model = Util::fillObject($data, $model);
        $model->save();
    }

    public function find($id){
        return $this->documentation->find($id);
    }

    public function delete($id) : void{
        $this->documentation->find($id)->delete();
    }

    public function getAll() : array {
        $documentations = $this->documentation->get();
        return empty($documentations) ? [] : $documentations->toArray();
    }
}

?>
