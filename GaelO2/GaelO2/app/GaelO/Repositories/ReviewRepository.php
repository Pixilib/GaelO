<?php

namespace App\GaelO\Repositories;

use App\GaelO\Interfaces\ReviewRepositoryInterface;
use App\GaelO\Util;
use App\Models\Review;
use Exception;

class ReviewRepository implements ReviewRepositoryInterface {


    public function __construct(Review $review){
        $this->review = $review;
    }

    private function create(array $data){
        $review = new Review();
        $model = Util::fillObject($data, $review);
        $model->save();
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

    public function getAll() : array {
        throw new Exception('Cant query all review');
    }


    public function getInvestigatorForm(int $visitId) : array {
        return $this->review->where('visit_id', $visitId)->where('local', true)->sole()->toArray();
    }

    public function unlockInvestigatorForm(int $visitId) : void {
        $reviewEntity = $this->review->where('visit_id', $visitId)->where('local', true)->sole();
        $reviewEntity->validated = false;
        $reviewEntity->save();
    }
}
