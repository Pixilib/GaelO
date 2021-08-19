<?php

namespace App\GaelO\Repositories;

use App\GaelO\Interfaces\Repositories\ReviewStatusRepositoryInterface;
use App\GaelO\Util;
use App\Models\ReviewStatus;

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

    public function updateReviewConclusion(int $visitId, string $studyName, string $reviewConclusionValue, ?array $targetLesions = null) : void {

        $model = $this->reviewStatus->where('visit_id', $visitId)->where('study_name', $studyName)->sole();
        $model->review_conclusion_value = $reviewConclusionValue;
        $model->target_lesions = $targetLesions;
        $model->review_conclusion_date = Util::now();
        $model->save();

    }

    public function updateReviewStatus(int $visitId, string $studyName, string $reviewStatus): void
    {

        $model = $this->reviewStatus->where('visit_id', $visitId)->where('study_name', $studyName)->sole();
        $model->review_status = $reviewStatus;
        $model->save();

    }

    public function updateReviewAvailability(int $visitId, string $studyName, bool $reviewAvailable): void
    {
        $reviewStatusEntity = $this->reviewStatus->where('visit_id', $visitId)->where('study_name', $studyName)->sole();
        $reviewStatusEntity['review_available'] = $reviewAvailable;
        $reviewStatusEntity->save();

    }

}
