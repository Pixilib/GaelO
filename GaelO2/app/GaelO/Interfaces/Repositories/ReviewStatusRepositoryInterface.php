<?php

namespace App\GaelO\Interfaces\Repositories;

interface ReviewStatusRepositoryInterface
{

    public function getReviewStatus(int $visitId, string $studyName): array;

    public function updateReviewConclusion(int $visitId, string $studyName, ?string $reviewConclusionValue, ?array $targetLesions): void;

    public function updateReviewStatus(int $visitId, string $studyName, string $reviewStatus): void;

    public function updateReviewAvailability(int $visitId, string $studyName, bool $reviewAvailable): void;
}
