<?php

namespace App\GaelO\Repositories;

use App\GaelO\Interfaces\Repositories\ReviewRepositoryInterface;
use App\GaelO\Util;
use App\Models\Review;

class ReviewRepository implements ReviewRepositoryInterface {


    public function __construct(Review $review){
        $this->review = $review;
    }

    private function create(array $data) : array {
        $review = new Review();
        $model = Util::fillObject($data, $review);
        $model->save();
        return $model->toArray();
    }

    private function update($id, array $data) : void {
        $model = $this->review->findOrFail($id);
        $model = Util::fillObject($data, $model);
        $model->save();
    }

    public function find($id) : array {
        return $this->review->findOrFail($id)->toArray();
    }

    public function delete($id) : void {
        $this->review->findOrFail($id)->delete();
    }

    public function getInvestigatorForm(int $visitId) : array {
        return $this->review->where('visit_id', $visitId)->where('local', true)->sole()->toArray();
    }

    public function unlockInvestigatorForm(int $visitId) : void {
        $reviewEntity = $this->review->where('visit_id', $visitId)->where('local', true)->sole();
        $reviewEntity->validated = false;
        $reviewEntity->save();
    }

    public function createReview(bool $local, int $visitId, string $studyName, int $userId, array $reviewData, bool $validated, bool $adjudication = false ) : int {

        $data['local'] = $local;
        $data['validated'] = $validated;
        $data['adjudication'] = $adjudication;
        $data['review_date'] = Util::now();
        $data['user_id'] =  $userId;
        $data['visit_id'] =  $visitId;
        $data['study_name'] = $studyName;
        $data['review_data'] = $reviewData;

        return $this->create($data)['id'];

    }

    public function updateReview(int $reviewId, int $userId, array $reviewData, bool $validated ) : void {

        $data['validated'] = $validated;
        $data['review_date'] = Util::now();
        $data['user_id'] =  $userId;
        $data['review_data'] = $reviewData;

        $this->update($reviewId, $data);

    }

    public function updateReviewFile(int $reviewId, array $associatedFile ) : void {

        $data['sent_files'] = $associatedFile;
        $this->update($reviewId, $data);

    }

    public function unlockReview(int $reviewId) : void {
        $reviewEntity = $this->review->findOrFail($reviewId);
        $reviewEntity->validated = false;
        $reviewEntity->save();
    }

    public function getReviewsForStudyVisit(string $studyName, int $visitId, bool $onlyValidated ) : array {

        $reviewQuery = $this->review
            ->where('study_name', $studyName)
            ->where('visit_id', $visitId)
            ->where('local', false)
            ->with('user');

        if($onlyValidated){
            $reviewQuery->where('validated', true);
        }

        $reviewEntity = $reviewQuery->get();

        return empty($reviewEntity) ? [] : $reviewEntity->toArray();

    }

    public function getReviewFormForStudyVisitUser(string $studyName, int $visitId, int $userId ) : array {
        $reviewEntity = $this->review
            ->where('study_name', $studyName)
            ->where('visit_id', $visitId)
            ->where('user_id', $userId)
            ->where('local', true)
            ->sole();

        return $reviewEntity->toArray();

    }

    /**
     * Return for each visit ID of the targeted visitType, the array of users having validated the review form
     */
    public function getUsersHavingReviewedForStudyVisitType(string $studyName, int $visitTypeId): array
    {

        $answer = $this->review
            ->with('user:id,username')
            ->whereHas('visit', function ($query) use ($visitTypeId) {
                $query->whereHas('visitType', function ($query) use ($visitTypeId) {
                    $query->where('id', $visitTypeId);
                });
            })
            ->where('study_name', $studyName)
            ->where('local', false)
            ->where('validated', true)
            ->select('visit_id','user_id')
            ->get();

        return $answer->count() === 0 ? []  : $answer->groupBy(['visit_id', 'user_id'])->toArray();
    }

    public function isExistingReviewForStudyVisitUser(string $studyName, int $visitId, int $userId) : bool{

        $reviewEntity = $this->review
            ->where('study_name', $studyName)
            ->where('visit_id', $visitId)
            ->where('user_id', $userId)
            ->where('local', false)
            ->get();

        return $reviewEntity->count() > 0 ? true : false;
    }

    public function getReviewsFromVisitIdArrayStudyName(array $visitId, string $studyName, bool $withTrashed) : array {

        $query = $this->review->whereIn('visit_id', $visitId)->where('study_name', $studyName)->where('local', false);
        if($withTrashed){
            $query->withTrashed();
        }
        $answer = $query->get();

        return $answer->count() === 0 ? [] : $answer->toArray();
    }

    public function getInvestigatorsFormsFromVisitIdArrayStudyName(array $visitId, string $studyName, bool $withTrashed) : array {

        $query = $this->review->whereIn('visit_id', $visitId)->where('study_name', $studyName)->where('local', true);
        if($withTrashed){
            $query->withTrashed();
        }
        $answer = $query->get();

        return $answer->count() === 0 ? [] : $answer->toArray();
    }

    //SK FAIRE UPDATE ASSOCIATED FILE
}
