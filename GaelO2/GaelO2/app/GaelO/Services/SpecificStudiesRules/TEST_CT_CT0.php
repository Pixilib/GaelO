<?php
namespace App\GaelO\Services\SpecificStudiesRules;

use App\GaelO\Adapters\MimeAdapter;
use App\GaelO\Constants\Constants;
use App\GaelO\Services\SpecificStudiesRules\AbstractStudyRules;

class TEST_CT_CT0 extends AbstractStudyRules {

    public function getInvestigatorValidationRules()  : array {
        return [
            'comment' => [
                'rule' => self::RULE_STRING,
                'optional' => false
            ]
        ];
    }

    public function getReviewerValidationRules(bool $adjudication) : array {
        return [
            'comment' => [
                'rule' => self::RULE_STRING,
                'optional' => false
            ]
        ];
    }

    public function getReviewStatus() : string {
        return Constants::REVIEW_STATUS_DONE;
    }

    public function getReviewConclusion() : string {
        return 'CR';
    }

    public function getAllowedKeyAndMimeTypeInvestigator() : array {
        return ['41' => MimeAdapter::getMimeFromExtension('csv')];
    }

    public function getAllowedKeyAndMimeTypeReviewer() : array {
        return ['2.5' => MimeAdapter::getMimeFromExtension('csv')];
    }

}
