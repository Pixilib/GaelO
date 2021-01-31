<?php

namespace App\GaelO\Repositories;

use App\GaelO\Interfaces\ReviewStatusRepositoryInterface;
use App\GaelO\Util;
use App\Models\ReviewStatus;
use Exception;

class ReviewStatusRepository implements ReviewStatusRepositoryInterface {

    public function __construct(ReviewStatus $reviewStatus) {
        $this->reviewStatus = $reviewStatus;
    }

    public function getReviewStatus(int $visitId, string $studyName) : array {
        return $this->reviewStatus->where('visit_id', $visitId)
        ->where('study_name', $studyName)
        ->sole()
        ->toArray();
    }

    public function updateReviewStatus(int $visitId, string $studyName, bool $reviewAvailable, string $reviewStatus, string $reviewConclusionValue, string $reviewConclusionDate ) : void {

        $array = [
            'review_available' => $reviewAvailable,
            'review_status' => $reviewStatus,
            'review_conclusion_value' => $reviewConclusionValue,
            'review_conclusion_date' => $reviewConclusionDate
        ];

        $model = $this->reviewStatus->where('visit_id', $visitId)->where('study_name', $studyName)->sole();
        $model = Util::fillObject($array, $model);
        $model->save();

    }





}
