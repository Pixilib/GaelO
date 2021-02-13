<?php
namespace App\GaelO\Services\SpecificStudiesRules;

use App\GaelO\Constants\Constants;
use App\GaelO\Services\SpecificStudiesRules\AbstractStudyRules;
use Illuminate\Support\Facades\Log;

class TEST_PT_PET0 extends AbstractStudyRules {

    public function checkInvestigatorFromValidity(array $data) {
        Log::info('checkInvestigatorFormValidity');
    }

    public function checkReviewFromValidity(array $data) {
        Log::info('checkReviewerFormValidity');
    }

    public function getReviewStatus() : string {
        return Constants::REVIEW_STATUS_DONE;
    }

    public function getReviewConclusion() : string {
        return 'CR';
    }

}
