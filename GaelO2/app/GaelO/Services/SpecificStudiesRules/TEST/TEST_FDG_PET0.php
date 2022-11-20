<?php

namespace App\GaelO\Services\SpecificStudiesRules\TEST;

use App\GaelO\Adapters\MimeAdapter;
use App\GaelO\Constants\Constants;
use App\GaelO\Interfaces\Repositories\ReviewRepositoryInterface;
use App\GaelO\Services\GaelOStudiesService\AbstractVisitRules;

class TEST_FDG_PET0 extends AbstractVisitRules
{
    private $reviewConclusion = null;

    private ReviewRepositoryInterface $reviewRepositoryInterface;
    protected string $studyName = "TEST";

    public function __construct(ReviewRepositoryInterface $reviewRepositoryInterface)
    {
        $this->reviewRepositoryInterface = $reviewRepositoryInterface;
    }

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

    public function getReviewStatus(): string
    {
        //Fetch visit validated review
        $reviews = $this->reviewRepositoryInterface->getReviewsForStudyVisit($this->studyName, $this->visitContext['id'], true);
        if(sizeof($reviews) > 0){
            $this->reviewConclusion = "Done";
            return Constants::REVIEW_STATUS_DONE;
        }else{
            return Constants::REVIEW_STATUS_NOT_DONE;
        }
    }

    public function getReviewConclusion(): ?string
    {
        return $this->reviewConclusion;
    }

    public function getAllowedKeyAndMimeTypeInvestigator(): array
    {
        return ['41' => MimeAdapter::getMimeFromExtension('csv'), '25' => MimeAdapter::getMimeFromExtension('zip')];
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
            'LastChemo' => '01/01/2021'
        ];
    }

    public function getAssociatedDataForReviewForm(): array
    {
        return [
            'Radiotherapy' => false
        ];
    }
}
