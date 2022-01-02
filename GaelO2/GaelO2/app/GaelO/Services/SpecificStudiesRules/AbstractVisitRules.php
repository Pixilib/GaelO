<?php

namespace App\GaelO\Services\SpecificStudiesRules;

use App\GaelO\Adapters\ValidatorAdapter;
use App\GaelO\Constants\Constants;
use Exception;

abstract class AbstractVisitRules
{

    const RULE_STRING = "string";
    const RULE_INT = "int";
    const RULE_SET = "set";
    const RULE_FLOAT = "float";

    abstract function getInvestigatorValidationRules(): array;

    abstract function getReviewerValidationRules(bool $adjudication): array;

    public function checkInvestigatorFormValidity(array $data, bool $validated): bool
    {

        $validatorAdapter = new ValidatorAdapter($validated);
        $investigatorsRules = $this->getInvestigatorValidationRules();
        $this->fillValidator($investigatorsRules, $validatorAdapter);
        return $validatorAdapter->validate($data);
    }

    public function checkReviewFormValidity(array $data, bool $validated, bool $adjudication): bool
    {
        $validatorAdapter = new ValidatorAdapter($validated);
        $reviewerRules = $this->getReviewerValidationRules($adjudication);
        $this->fillValidator($reviewerRules, $validatorAdapter);
        return $validatorAdapter->validate($data);
    }

    protected function fillValidator(array $rules, ValidatorAdapter $validatorAdapter)
    {

        foreach ($rules as $name => $details) {
            switch ($details['rule']) {
                case self::RULE_STRING:
                    $validatorAdapter->addValidatorString($name, $details['optional']);
                    break;
                case self::RULE_INT:
                    $validatorAdapter->addValidatorInt($name, $details['optional'], $details['min'], $details['max']);
                    break;
                case self::RULE_FLOAT:
                    $validatorAdapter->addValidatorFloat($name, $details['optional'], $details['min'], $details['max']);
                    break;
                case self::RULE_SET:
                    $validatorAdapter->addSetValidator($name, $details['values'], $details['optional']);
                    break;
                default:
                    throw new Exception('Unknown Rule');
            }
        }
    }

    abstract function getReviewStatus(): string;

    abstract function getReviewConclusion(): string;

    abstract function getAllowedKeyAndMimeTypeInvestigator(): array;

    abstract function getAllowedKeyAndMimeTypeReviewer(): array;

    public function getReviewAvailability(string $reviewStatus): bool
    {
        if ($reviewStatus === Constants::REVIEW_STATUS_DONE) {
            //If Done reached make the review unavailable for review
            return false;
        } else {
            //Needed in case of deletion of a review (even if true by default initialy, need to come back if deletion)
            return true;
        }
    }

}
