<?php

namespace App\GaelO\Repositories;

use App\GaelO\Interfaces\PersistenceInterface;
use App\GaelO\Interfaces\ReviewRepositoryInterface;
use App\GaelO\Util;
use App\Models\Review;
use Exception;

class ReviewRepository implements PersistenceInterface, ReviewRepositoryInterface {


    public function __construct(Review $review){
        $this->review = $review;
    }

    public function create(array $data){
        $review = new Review();
        $model = Util::fillObject($data, $review);
        $model->save();
    }

    public function update($id, array $data) : void {
        $model = $this->review->find($id);
        $model = Util::fillObject($data, $model);
        $model->save();
    }

    public function find($id){
        return $this->review->findOrFail($id)->toArray();
    }

    public function delete($id) : void {
        $this->review->find($id)->delete();
    }

    public function getAll() : array {
        throw new Exception('Cant query all review');
    }


    public function getInvestigatorForm(int $visitId) : array {
        return $this->review->where('visit_id', $visitId)->where('local', true)->firstOrFail()->toArray();
    }

    public function unlockInvestigatorForm(int $visitId) : void {
        $reviewEntity = $this->review->where('visit_id', $visitId)->where('local', true)->firstOrFail();
        $reviewEntity->validated = false;
        $reviewEntity->save();
    }
}
