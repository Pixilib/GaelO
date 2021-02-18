<?php

namespace App\GaelO\Services\SpecificStudiesRules;

use App\GaelO\Constants\Constants;

abstract class AbstractStudyRules {

    abstract function checkInvestigatorFormValidity(array $data) ;

    abstract function checkReviewFormValidity(array $data) ;

    abstract function getReviewStatus() : string ;

    abstract function getReviewConclusion() : string ;

    public function getReviewAvailability(string $reviewStatus)  : bool {
		if ( $reviewStatus === Constants::REVIEW_STATUS_DONE ) {
            //If Done reached make the review unavailable for review
            return false;
		} else {
            //Needed in case of deletion of a review (even if true by default initialy, need to come back if deletion)
            return true;
		}
	}

}
