<?php

namespace App\GaelO\Interfaces\Repositories;

interface ReviewStatusRepositoryInterface
{

    public function getReviewStatus(int $visitId, string $studyName): array;

    public function updateReviewStatusAndConclusion(int $visitId, string $studyName, string $reviewStatus, ?string $reviewConclusionValue, ?array $targetLesions): void;

    public function updateReviewAvailability(int $visitId, string $studyName, bool $reviewAvailable): void;
}
