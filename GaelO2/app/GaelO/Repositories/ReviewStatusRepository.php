<?php

namespace App\GaelO\Repositories;

use App\GaelO\Constants\Enums\ReviewStatusEnum;
use App\GaelO\Interfaces\Repositories\ReviewStatusRepositoryInterface;
use App\GaelO\Util;
use App\Models\ReviewStatus;

class ReviewStatusRepository implements ReviewStatusRepositoryInterface
{

    private ReviewStatus $reviewStatusModel;

    public function __construct(ReviewStatus $reviewStatus)
    {
        $this->reviewStatusModel = $reviewStatus;
    }

    public function getReviewStatus(int $visitId, string $studyName): array
    {
        return $this->reviewStatusModel->where('visit_id', $visitId)
            ->where('study_name', $studyName)
            ->sole()
            ->toArray();
    }

    public function updateReviewAvailabilityStatusAndConclusion(int $visitId, string $studyName, bool $availability, string $reviewStatus, ?string $reviewConclusionValue, ?array $targetLesions): void
    {
        //If a conclusion is set, write the date of conclusion (now)
        $conclusionDate = $reviewStatus === ReviewStatusEnum::DONE->value ? Util::now() : null ;
        $this->reviewStatusModel->updateOrCreate(
            ['visit_id' => $visitId, 'study_name' => $studyName],
            [
                'review_available' => $availability,
                'review_status' => $reviewStatus,
                'review_conclusion_value' => $reviewConclusionValue,
                'target_lesions' => $targetLesions,
                'review_conclusion_date' => $conclusionDate
            ]

        );
    }

    public function updateReviewAvailability(int $visitId, string $studyName, bool $reviewAvailable): void
    {

        $this->reviewStatusModel->updateOrCreate(
            ['visit_id' => $visitId, 'study_name' => $studyName],
            ['review_available' => $reviewAvailable]
        );
    }
}
