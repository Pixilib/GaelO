<?php

namespace App\GaelO\Services\SpecificStudiesRules\TEST;

use App\GaelO\Adapters\MimeAdapter;
use App\GaelO\Constants\Constants;
use App\GaelO\Services\GaelOStudiesService\AbstractVisitRules;

class TEST_FDG_PET0 extends AbstractVisitRules
{

    public function getInvestigatorValidationRules(): array
    {
        return [
            'glycaemia' => [
                'rule' => self::RULE_NUMBER,
                'optional' => true,
                'min' => 0,
                'max' => 20
            ],
            'glycaemiaNotDone' => [
                'rule' => self::RULE_BOOLEAN,
                'optional' => true
            ],
            'radiotherapyThreeMonths' => [
                'rule' => self::RULE_BOOLEAN,
                'optional' => false
            ],
            'csfThreeWeeks' => [
                'rule' => self::RULE_BOOLEAN,
                'optional' => false
            ],
            'biopsy' => [
                'rule' => self::RULE_BOOLEAN,
                'optional' => false
            ],
            'biopsyDate' => [
                'rule' => self::RULE_STRING,
                'optional' => true
            ],
            'biopsyLocation' => [
                'rule' => self::RULE_SET,
                'values' => TEST::TEST_LOCALIZATIONS,
                'optional' => true
            ],
            'infection' => [
                'rule' => self::RULE_BOOLEAN,
                'optional' => false
            ],
            'infectionDate' => [
                'rule' => self::RULE_STRING,
                'optional' => true
            ],
            'infectionLocation' => [
                'rule' => self::RULE_SET,
                'values' => TEST::TEST_LOCALIZATIONS,
                'optional' => true
            ],
            'comments' => [
                'rule' => self::RULE_STRING,
                'optional' => true
            ]
        ];
    }

    public function getReviewerValidationRules(bool $adjudication): array
    {
        return [
            'comments' => [
                'rule' => self::RULE_STRING,
                'optional' => false
            ]
        ];
    }

    public function getReviewStatus(): string
    {
        return Constants::REVIEW_STATUS_DONE;
    }

    public function getReviewConclusion(): string
    {
        return 'CR';
    }

    public function getAllowedKeyAndMimeTypeInvestigator(): array
    {
        return ['41' => MimeAdapter::getMimeFromExtension('csv')];
    }

    public function getAllowedKeyAndMimeTypeReviewer(): array
    {
        return [];
    }

    public function getTargetLesion(): ?array
    {
        return null;
    }

    public function getAssociatedDataForInvestigatorForm(): array
    {
        return [
            [
                'LastChemo' => '01/01/2021'
            ]
        ];
    }

    public function getAssociatedDataForReviewForm(): array
    {
        return [
            [
                'Radiotherapy' => false
            ]
        ];
    }
}
