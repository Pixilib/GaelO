<?php
namespace App\GaelO\Services\SpecificStudiesRules;

use App\GaelO\Adapters\MimeAdapter;
use App\GaelO\Adapters\ValidatorAdapter;
use App\GaelO\Constants\Constants;
use App\GaelO\Services\SpecificStudiesRules\AbstractStudyRules;

class TEST_PT_PET0 extends AbstractStudyRules {

    public function checkInvestigatorFormValidity(array $data, bool $validated)  : bool {

        $validatorAdapter = new ValidatorAdapter($validated);
        $validatorAdapter->addValidatorString('comment', false);
        return $validatorAdapter->validate($data);
    }

    public function checkReviewFormValidity(array $data, bool $validated, bool $adjudication) : bool {
        //Here no adjudication if needed can be splitted in two privates methods
        $validatorAdapter = new ValidatorAdapter($validated);
        $validatorAdapter->addValidatorString('comment', false);
        return $validatorAdapter->validate($data);
    }

    public function getReviewStatus() : string {
        return Constants::REVIEW_STATUS_DONE;
    }

    public function getReviewConclusion() : string {
        return 'CR';
    }

    public function getAllowedKeyAndMimeTypeInvestigator() : array {
        return [];
    }

    public function getAllowedKeyAndMimeTypeReviewer() : array {
        return ['41' => MimeAdapter::getMimeFromExtension('csv')];
    }

}
