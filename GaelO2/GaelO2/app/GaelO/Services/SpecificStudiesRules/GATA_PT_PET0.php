<?php

namespace App\GaelO\Services\SpecificStudiesRules;

use App\GaelO\Adapters\MimeAdapter;
use App\GaelO\Constants\Constants;
use App\GaelO\Services\SpecificStudiesRules\AbstractVisitRules;

class GATA_PT_PET0 extends AbstractVisitRules
{

    public function getInvestigatorValidationRules(): array
    {
        return [
            'glycaemia' => [
                'rule' => self::RULE_NUMBER,
                'optional' => true,
                'min' => null,
                'max' => null
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
                'values' => OPTIONS,
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
                'values' => OPTIONS,
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
        return [];
    }

    public function getReviewStatus(): string
    {
        return Constants::REVIEW_STATUS_NOT_NEEDED;
    }

    public function getReviewConclusion(): string
    {
        return '';
    }

    public function getAllowedKeyAndMimeTypeInvestigator(): array
    {
        return [
            'complementaryData' => MimeAdapter::getMimeFromExtension('csv'),
            'otherData' => MimeAdapter::getMimeFromExtension('csv'),
        ];
    }

    public function getAllowedKeyAndMimeTypeReviewer(): array
    {
        return ['2.5' => MimeAdapter::getMimeFromExtension('csv')];
    }

    public function getTargetLesion(): ?array
    {
        return null;
    }

    public function getAssociatedDataForInvestigatorForm(): array
    {
        return [
            'TestData1' => '01/01/2021',
            'TestData2' => 341,
            'TestData3' => true,
            'TestData4' => null
        ];
    }

    public function getAssociatedDataForReviewForm(): array
    {
        return [
            'Radiotherapy' => false
        ];
    }
}

define('OPTIONS', [
    '',
    'Cervical right',
    'Cervical left',
    'Supraclavicular right',
    'Supraclavicular left',
    'Axillary right',
    'Axillary left',
    'Inguinal right',
    'Inguinal left',
    'Mediastinal',
    'Pulmonary hilar',
    'Retroperitoneal / Para-aortic',
    'Mesenteric',
    'Iliac right',
    'Splenic hilar',
    'Epitrochlear right',
    'Epitrochlear left',
    'Popliteal right',
    'Popliteal left',
    'Other nodal involvement',
    'Liver',
    'Ascites',
    'Pleura',
    'Lung',
    'Spleen',
    'Pericardium',
    'Breast',
    'Gonadal',
    'Kidney',
    'Adrenal',
    'Thyroid',
    'Skin',
    'Bone',
    'Blood',
    'Tonsil',
    'Cavum',
    'Parotid',
    'Orbit',
    'Sinus',
    'Other ORL area',
    'Oesophagus',
    'Stomach',
    'Duodenum',
    'Colon',
    'Caecum',
    'Ileon',
    'Rectum',
    'Other digestive area',
    'Bone marrow',
    'Urinary tract',
    'Soft tissues',
    'Heart',
    'Other extra-nodal involvement',
    'Splenic node',
    'Hepatic node',
    'Pancreas',
    'Meningeal',
    'Other Central Nervous System',
    'Small intestine',
    'Ileo-caecal junction'
]);
