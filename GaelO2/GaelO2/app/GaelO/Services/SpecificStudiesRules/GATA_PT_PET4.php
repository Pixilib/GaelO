<?php
namespace App\GaelO\Services\SpecificStudiesRules;

use App\GaelO\Adapters\MimeAdapter;
use App\GaelO\Constants\Constants;
use App\GaelO\Services\SpecificStudiesRules\AbstractVisitRules;

class GATA_PT_PET4 extends AbstractVisitRules {

    public function getInvestigatorValidationRules()  : array {
        return [];
    }

    public function getReviewerValidationRules(bool $adjudication) : array {
        return [
            'lyric' => [
                'rule' => self::RULE_SET,
                'values' => [
                    'N/A',
                    'CMR',
                    'PMR',
                    'NMR',
                    'IR1',
                    'IR2',
                    'IR3',
                    'PD',                
                ],
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
