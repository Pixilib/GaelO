<?php

namespace App\GaelO\Interfaces\Repositories;

interface ReviewRepositoryInterface
{

    public function find($id): array;

    public function delete($id): void;

    public function getInvestigatorForm(int $visitId, bool $withUser): array;

    public function unlockInvestigatorForm(int $visitId): void;

    public function createReview(bool $local, int $visitId, string $studyName, int $userId, array $reviewData, bool $validated, bool $adjudication = false): int;

    public function updateReview(int $reviewId, int $userId, array $reviewData, bool $validated): void;

    public function updateReviewFile(int $reviewId, array $associatedFile): void;

    public function getReviewFormForStudyVisitUser(string $studyName, int $visitId, int $userId): array;

    public function isExistingReviewForStudyVisitUser(string $studyName, int $visitId, int $userId): bool;

    public function getReviewsForStudyVisit(string $studyName, int $visitId, bool $onlyValidated): array;

    public function unlockReview(int $reviewId): void;

    public function getUsersHavingReviewedForStudyVisitType(string $studyName, int $visitTypeId): array;

    public function getReviewsFromVisitIdArrayStudyName(array $visitId, string $studyName, bool $withTrashed): array;

    public function getInvestigatorsFormsFromVisitIdArrayStudyName(array $visitId, string $studyName, bool $withTrashed, bool $withUser = false): array;
}
