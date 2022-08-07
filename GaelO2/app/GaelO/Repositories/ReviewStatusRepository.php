<?php

namespace App\GaelO\Repositories;

use App\GaelO\Interfaces\Repositories\ReviewStatusRepositoryInterface;
use App\GaelO\Util;
use App\Models\ReviewStatus;

class ReviewStatusRepository implements ReviewStatusRepositoryInterface
{

    private ReviewStatus $reviewStatus;

    public function __construct(ReviewStatus $reviewStatus)
    {
        $this->reviewStatus = $reviewStatus;
    }

    public function getReviewStatus(int $visitId, string $studyName): array
    {
        return $this->reviewStatus->where('visit_id', $visitId)
            ->where('study_name', $studyName)
            ->sole()
            ->toArray();
    }

    public function updateReviewConclusion(int $visitId, string $studyName, ?string $reviewConclusionValue, ?array $targetLesions): void
    {
        //If a conclusion is set, write the date of conclusion (now)
        $conclusionDate = $reviewConclusionValue === null ? null :  Util::now();
        $this->reviewStatus->updateOrCreate(
            ['visit_id' => $visitId, 'study_name' => $studyName],
            ['review_conclusion_value' => $reviewConclusionValue, 'target_lesions' => $targetLesions, 'review_conclusion_date' => $conclusionDate]
        );
    }

    public function updateReviewStatus(int $visitId, string $studyName, string $reviewStatus): void
    {
        $this->reviewStatus->updateOrCreate(
            ['visit_id' => $visitId, 'study_name' => $studyName],
            ['review_status' => $reviewStatus]
        );
    }

    public function updateReviewAvailability(int $visitId, string $studyName, bool $reviewAvailable): void
    {

        $this->reviewStatus->updateOrCreate(
            ['visit_id' => $visitId, 'study_name' => $studyName],
            ['review_available' => $reviewAvailable]
        );
    }
}
