<?php

namespace App\GaelO\Interfaces;

interface ReviewRepositoryInterface {

    public function find($id) : array ;

    public function delete($id) : void ;

    public function getInvestigatorForm(int $visitId) : array ;

    public function unlockInvestigatorForm(int $visitId) : void ;

    public function createReview(bool $local, int $visitId, string $studyName, int $userId, array $reviewData, bool $validated, bool $adjudication = false ) : int ;

    public function updateReview(int $reviewId, int $userId, array $reviewData, bool $validated ) : void ;
}
