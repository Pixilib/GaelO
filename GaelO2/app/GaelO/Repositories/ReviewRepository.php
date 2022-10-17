<?php

namespace App\GaelO\Repositories;

use App\GaelO\Interfaces\Repositories\ReviewRepositoryInterface;
use App\GaelO\Util;
use App\Models\Review;

class ReviewRepository implements ReviewRepositoryInterface
{
    private Review $reviewModel;

    public function __construct(Review $review)
    {
        $this->reviewModel = $review;
    }

    public function find($id): array
    {
        return $this->reviewModel->findOrFail($id)->toArray();
    }

    public function delete($id): void
    {
        $this->reviewModel->findOrFail($id)->delete();
    }

    public function getInvestigatorForm(int $visitId, bool $withUser): array
    {
        $query = $this->reviewModel->where('visit_id', $visitId)->where('local', true);
        if ($withUser) $query->with('user');

        return $query->sole()->toArray();
    }

    public function unlockInvestigatorForm(int $visitId): void
    {
        $reviewEntity = $this->reviewModel->where('visit_id', $visitId)->where('local', true)->sole();
        $reviewEntity->validated = false;
        $reviewEntity->save();
    }

    public function createReview(bool $local, int $visitId, string $studyName, int $userId, array $reviewData, bool $validated, bool $adjudication): int
    {

        $review = new Review();

        $review->local = $local;
        $review->validated = $validated;
        $review->adjudication = $adjudication;
        $review->review_date = Util::now();
        $review->user_id =  $userId;
        $review->visit_id =  $visitId;
        $review->study_name = $studyName;
        $review->review_data = $reviewData;

        $review->save();
        return $review->toArray()['id'];
    }

    public function updateReview(int $reviewId, int $userId, array $reviewData, bool $validated): void
    {

        $review = $this->reviewModel->findOrFail($reviewId);
        $review->validated = $validated;
        $review->review_date = Util::now();
        $review->user_id =  $userId;
        $review->review_data = $reviewData;
        $review->save();
    }

    public function updateReviewFile(int $reviewId, array $associatedFile): void
    {

        $review = $this->reviewModel->findOrFail($reviewId);
        $review->sent_files = $associatedFile;
        $review->save();
    }

    public function unlockReview(int $reviewId): void
    {
        $reviewEntity = $this->reviewModel->findOrFail($reviewId);
        $reviewEntity->validated = false;
        $reviewEntity->save();
    }

    public function getReviewsForStudyVisit(string $studyName, int $visitId, bool $onlyValidated): array
    {
        $reviewQuery = $this->reviewModel
            ->where('study_name', $studyName)
            ->where('visit_id', $visitId)
            ->where('local', false)
            ->with('user');

        if ($onlyValidated) $reviewQuery->where('validated', true);

        $reviewEntity = $reviewQuery->get();

        return empty($reviewEntity) ? [] : $reviewEntity->toArray();
    }

    public function getReviewFormForStudyVisitUser(string $studyName, int $visitId, int $userId): array
    {
        $reviewEntity = $this->reviewModel
            ->where('study_name', $studyName)
            ->where('visit_id', $visitId)
            ->where('user_id', $userId)
            ->where('local', false)
            ->sole();

        return $reviewEntity->toArray();
    }

    /**
     * Return the array of users having validated the review forms for given study
     */
    public function getStudyReviewsGroupedByUserIds(string $studyName): array
    {

        $answer = $this->reviewModel
            ->with('user:id,firstname,lastname')
            ->where('study_name', $studyName)
            ->where('local', false)
            ->where('validated', true)
            ->select('visit_id', 'user_id')
            ->get();

        return $answer->count() === 0 ? []  : $answer->groupBy(['visit_id', 'user_id'])->toArray();
    }

    public function isExistingReviewForStudyVisitUser(string $studyName, int $visitId, int $userId): bool
    {

        $reviewEntity = $this->reviewModel
            ->where('study_name', $studyName)
            ->where('visit_id', $visitId)
            ->where('user_id', $userId)
            ->where('local', false)
            ->get();

        return $reviewEntity->count() > 0 ? true : false;
    }

    public function getReviewsFromVisitIdArrayStudyName(array $visitId, string $studyName, bool $withTrashed, bool $withUser = false): array
    {

        $query = $this->reviewModel->whereIn('visit_id', $visitId)->where('study_name', $studyName)->where('local', false);
        if ($withUser) {
            $query->with('user');
        }
        if ($withTrashed) {
            $query->withTrashed();
        }
        $answer = $query->has('visit')->with('visit', 'visit.visitType', 'visit.visitType.visitGroup')->get();

        return $answer->count() === 0 ? [] : $answer->toArray();
    }

    public function getInvestigatorsFormsFromVisitIdArrayStudyName(array $visitId, string $studyName, bool $withTrashed, bool $withUser = false): array
    {

        $query = $this->reviewModel->whereIn('visit_id', $visitId)->where('study_name', $studyName)->where('local', true);
        if ($withUser) {
            $query->with('user');
        }

        if ($withTrashed) {
            $query->withTrashed();
        }
        $answer = $query->has('visit')->with('visit', 'visit.visitType', 'visit.visitType.visitGroup')->get();

        return $answer->count() === 0 ? [] : $answer->toArray();
    }
}
