<?php

namespace App\GaelO\Interfaces;

interface ReviewStatusRepositoryInterface {

    public function getReviewStatus(int $visitId, string $studyName) : array ;

    public function updateReviewStatus(int $visitId, string $studyName, bool $reviewAvailable, string $reviewStatus, string $reviewConclusionValue, string $reviewConclusionDate ) : void ;

}
