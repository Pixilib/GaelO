<?php

namespace App\GaelO\Repositories;

use App\GaelO\Interfaces\PersistenceInterface;
use App\GaelO\Util;
use App\ReviewStatus;
use Exception;

class ReviewStatusRepository implements PersistenceInterface {

    public function __construct(ReviewStatus $reviewStatus) {
        $this->reviewStatus = $reviewStatus;
    }

    public function create(array $data){
        $patient = new ReviewStatus();
        $model = Util::fillObject($data, $patient);
        $model->save();

    }

    public function update($code, array $data) : void {
        $model = $this->reviewStatus->find($code);
        $model = Util::fillObject($data, $model);
        $model->save();
    }

    public function find($id){
        throw new Exception('Not Callable');
    }

    public function getAll() : array {
        throw new Exception('Not Callable');
    }

    public function getReviewStatus($id, $studyName){
        return $this->reviewStatus->where('visit_id', $id)->where('study_name', $studyName)->firstOrFail()->toArray();
    }

    public function delete($id) :void {
        $this->reviewStatus->find($id)->delete();
    }





}
