<?php
namespace App\GaelO\Services\SpecificStudiesRules;

use App\GaelO\Adapters\MimeAdapter;
use App\GaelO\Constants\Constants;
use App\GaelO\Services\SpecificStudiesRules\AbstractVisitRules;

class TEST_PT_PET0 extends AbstractVisitRules {

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
        return [];
    }

    public function getAllowedKeyAndMimeTypeReviewer() : array {
        return ['41' => MimeAdapter::getMimeFromExtension('csv')];
    }

    public function getTargetLesion() : ?array {
        return null;
    }

}
