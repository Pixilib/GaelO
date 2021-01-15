<?php

namespace App\GaelO\Interfaces;

interface ReviewStatusInterface {

    public function getReviewStatus(int $visitId, string $studyName) : array ;

    public function updateReviewStatus(int $visitId, string $studyName, bool $reviewAvailable, string $reviewStatus, string $reviewConclusionValue, string $reviewConclusionDate ) : void ;

}
