<?php

namespace App\GaelO\Repositories;

use App\Study;
use App\GaelO\Interfaces\PersistenceInterface;
use App\GaelO\UseCases\GetStudy\StudyEntity;
use App\GaelO\Util;

class StudyRepository implements PersistenceInterface {

    public function __construct(Study $study){
        $this->study = $study;
    }

    public function create(array $data){
        $study = new Study();
        $model = Util::fillObject($data, $study);
        $model->save();
    }

    public function update($name, array $data) : void{
        $model = $this->study->find($name);
        $model = Util::fillObject($data, $model);
        $model->save();
    }

    public function find($name){
        return $this->study->find($name)->firstOrFail()->toArray();
    }

    public function delete($name) : void {
        $this->study->find($name)->delete();
    }

    public function getAll() : array {
        $studies = $this->study->get();
        return empty($studies) ? [] : $studies->toArray();
    }

    public function addStudy(String $name, String $patientCodePrefix) : void {
        $data = [
            'name'=>$name,
            'patient_code_prefix'=>$patientCodePrefix
        ];

        $this->create($data);

    }

    public function isExistingStudy($name) : bool {
        $studies = $this->study->withTrashed()->where('name',$name)->get();
        return $studies->count()> 0 ? true : false ;

    }

    public function getStudies(bool $withTrashed = false) : array {
        if($withTrashed){
            $studies = $this->study->withTrashed()->get();
        }else {
            $studies = $this->study->get();
        }
        return $studies->count() == 0 ? [] : $studies->toArray() ;
    }

    public function getStudiesDetails() : array {
        $studiesDetails = $this->study->withTrashed()->with(['visitGroupDetails'])->get();
        return $studiesDetails->toArray();
    }

    public function reactivateStudy(string $name) : void {
        $this->study->withTrashed()->find($name)->restore();
    }

    public function getStudy(string $name) : StudyEntity {
        $study = $this->study->where('name', $name)->get()->toArray();
        return StudyEntity::fillFromDBReponseArray($study[0]);
    }
}

?>
