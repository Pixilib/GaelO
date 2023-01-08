<?php

namespace App\GaelO\Services\SpecificStudiesRules\TEST;

use App\GaelO\Adapters\MimeAdapter;
use App\GaelO\Services\GaelOStudiesService\AbstractVisitRules;

class TEST_FDG_PET0 extends AbstractVisitRules
{

    protected string $studyName = "TEST";

    public static function getInvestigatorValidationRules(): array
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
                'optional' => false
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
                'rule' => self::RULE_DATE,
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
                'rule' => self::RULE_DATE,
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

    public static function getReviewerValidationRules(): array
    {
        return [
            'comments' => [
                'rule' => self::RULE_STRING,
                'optional' => false
            ]
        ];
    }

    public static function getReviewerAdjudicationValidationRules(): array
    {
        return [];
    }

    public static function getAllowedKeysAndMimeTypesInvestigator(): array
    {
        return ['41' => MimeAdapter::getMimesFromExtension('csv'), '25' => MimeAdapter::getMimesFromExtension('zip')];
    }

    public static function getAllowedKeysAndMimeTypesReviewer(): array
    {
        return [];
    }

    public static function getAllowedKeysAndMimeTypesAdjudication(): array
    {
        return [];
    }

    public static function getVisitDecisionClass(): string
    {
        return TEST_FDG_PET0_DECISION::class;
    }

}
