<?php

namespace App\GaelO\Repositories;

use App\Models\Study;
use App\GaelO\Interfaces\Repositories\StudyRepositoryInterface;
use App\GaelO\Util;
use Exception;

class StudyRepository implements StudyRepositoryInterface {

    public function __construct(Study $study){
        $this->study = $study;
    }

    public function create(array $data) : void {
        $study = new Study();
        $model = Util::fillObject($data, $study);
        $model->save();
    }

    public function update($name, array $data) : void {
        $model = $this->study->find($name);
        $model = Util::fillObject($data, $model);
        $model->save();
    }

    public function find($name) : array {
        return $this->study->findOrFail($name)->toArray();
    }

    public function delete($name) : void {
        $this->study->findOrFail($name)->delete();
    }

    public function getAll() : array {
        throw new Exception('Use Get Studies instead');
    }

    public function addStudy(String $name, String $patientCodePrefix) : void {
        $data = [
            'name'=>$name,
            'patient_code_prefix'=>$patientCodePrefix
        ];

        $this->create($data);
    }

    public function isExistingStudy($name) : bool {
        $studies = $this->study->withTrashed()->where('name', $name)->get();
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

    public function getAllStudiesWithDetails() : array {
        $studiesDetails = $this->study->withTrashed()->with('visitGroupDetails')->get();
        return $studiesDetails->toArray();
    }

    public function getStudyDetails(string $name) : array {
        $studiesDetails = $this->study->with('visitGroupDetails')->findOrFail($name);
        return $studiesDetails->toArray();
    }

    public function reactivateStudy(string $name) : void {
        $this->study->withTrashed()->findOrFail($name)->restore();
    }

}
