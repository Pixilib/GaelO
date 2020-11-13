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
        return $model->toArray();
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

    public function createDocumentation(string $name, string $documentDate, string $studyName, string $version, bool $investigator,
                bool $controller, bool $monitor, bool $reviewer) : array {

        $data = [
            'name'=>$name,
            'document_date'=>$documentDate,
            'study_name'=>$studyName,
            'version'=>$version,
            'investigator'=>$investigator,
            'controller'=>$controller,
            'monitor'=>$monitor,
            'reviewer'=>$reviewer,
        ];

        return $this->create($data);

    }
}

?>
