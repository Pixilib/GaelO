<?php

namespace App\GaelO\Repositories;

use App\GaelO\Interfaces\PersistenceInterface;
use App\GaelO\Interfaces\ReviewStatusInterface;
use App\GaelO\Util;
use App\Models\ReviewStatus;
use Exception;

class ReviewStatusRepository implements PersistenceInterface, ReviewStatusInterface {

    public function __construct(ReviewStatus $reviewStatus) {
        $this->reviewStatus = $reviewStatus;
    }

    public function create(array $data){
        throw new Exception('Created by Visit creation');
    }

    public function update($code, array $data) : void {
        throw new Exception('use updateReviewStatus');
    }

    public function find($id){
        throw new Exception('Not Callable');
    }

    public function getAll() : array {
        throw new Exception('Not Callable');
    }

    public function getReviewStatus(int $visitId, string $studyName) : array {
        return $this->reviewStatus->where('visit_id', $visitId)->where('study_name', $studyName)->firstOrFail()->toArray();
    }

    public function delete($id) :void {
        throw new Exception('Cant Delete Review Status');
    }

    public function updateReviewStatus(int $visitId, string $studyName, bool $reviewAvailable, string $reviewStatus, string $reviewConclusionValue, string $reviewConclusionDate ) : void {

        $array = [
            'review_available' => $reviewAvailable,
            'review_status' => $reviewStatus,
            'review_conclusion_value' => $reviewConclusionValue,
            'review_conclusion_date' => $reviewConclusionDate
        ];

        $model = $this->reviewStatus->where('visit_id', $visitId)->where('study_name', $studyName)->firstOrFail();
        $model = Util::fillObject($array, $model);
        $model->save();

    }





}
