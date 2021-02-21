<?php

namespace App\GaelO\Repositories;

use App\GaelO\Interfaces\ReviewRepositoryInterface;
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
        $model = $this->review->find($id);
        $model = Util::fillObject($data, $model);
        $model->save();
    }

    public function find($id) : array {
        return $this->review->findOrFail($id)->toArray();
    }

    public function delete($id) : void {
        $this->review->find($id)->delete();
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

    //SK A TESTER
    public function getReviewsForStudyVisit(string $studyName, int $visitId, bool $onlyValidated ) : array {

        $reviewQuery = $this->review
            ->where('study_name', $studyName)
            ->where('visit_id', $visitId);

        if($onlyValidated){
            $reviewQuery->where('validated', true);
        }

        $reviewEntity = $reviewQuery->get();

        return empty($reviewEntity) ? [] : $reviewEntity->toArray();

    }

    //SK A TESTER
    public function getReviewFormForStudyVisitUser(string $studyName, int $visitId, int $userId ) : array {
        $reviewEntity = $this->review
            ->where('study_name', $studyName)
            ->where('visit_id', $visitId)
            ->where('user_id', $userId)
            ->sole();

        return $reviewEntity->toArray();

    }

    //SK A tester
    public function isExistingFormForStudyVisitUser(string $studyName, int $visitId, int $userId) : bool{

        $reviewEntity = $this->review
            ->where('study_name', $studyName)
            ->where('visit_id', $visitId)
            ->where('user_id', $userId)
            ->get();

        return $reviewEntity->count() > 0 ? true : false;
    }

    //SK FAIRE UPDATE ASSOCIATED FILE
}
