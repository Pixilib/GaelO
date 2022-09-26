<?php

namespace App\GaelO\Services\GaelOStudiesService;

use App\GaelO\Adapters\ValidatorAdapter;
use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOException;

abstract class AbstractVisitRules
{

    const RULE_STRING = "string";
    const RULE_INT = "int";
    const RULE_NUMBER = "number";
    const RULE_SET = "set";
    const RULE_BOOLEAN = "boolean";
    const RULE_DATE = "date";

    protected array $data;
    protected array $visitContext;

    abstract function getInvestigatorValidationRules(): array;

    abstract function getReviewerValidationRules(bool $adjudication): array;

    public function getInvestigatorInputNames() : array {
        $rules = $this->getInvestigatorValidationRules();
        return array_unique(array_keys($rules));
    }

    public function getReviewerInputNames() : array {
        $rules = $this->getReviewerValidationRules(true);
        $adjudicationRules = $this->getReviewerValidationRules(false);
        $inputs = [...array_keys($rules), ...array_keys($adjudicationRules)];
        return array_unique($inputs);
    }

    public function setVisitContext(array $visitContext)
    {
        $this->visitContext = $visitContext;
    }

    public function setFormData(array $data)
    {
        $this->data = $data;
    }

    public function checkInvestigatorFormValidity(bool $validated): bool
    {

        $validatorAdapter = new ValidatorAdapter($validated);
        $investigatorsRules = $this->getInvestigatorValidationRules();
        $this->fillValidator($investigatorsRules, $validatorAdapter);
        return $validatorAdapter->validate($this->data);
    }

    public function checkReviewFormValidity(bool $validated, bool $adjudication): bool
    {
        $validatorAdapter = new ValidatorAdapter($validated);
        $reviewerRules = $this->getReviewerValidationRules($adjudication);
        $this->fillValidator($reviewerRules, $validatorAdapter);
        return $validatorAdapter->validate($this->data);
    }

    protected function fillValidator(array $rules, ValidatorAdapter $validatorAdapter)
    {
        foreach ($rules as $name => $details) {
            $this->addValidator($name, $details, $validatorAdapter);
        }
    }

    protected function addValidator(string $name, array $details, ValidatorAdapter $validatorAdapter)
    {
        switch ($details['rule']) {
            case self::RULE_STRING:
                $validatorAdapter->addValidatorString($name, $details['optional']);
                break;
            case self::RULE_INT:
                $validatorAdapter->addValidatorInt($name, $details['optional'], $details['min'], $details['max']);
                break;
            case self::RULE_SET:
                $validatorAdapter->addSetValidator($name, $details['values'], $details['optional']);
                break;
            case self::RULE_BOOLEAN:
                $validatorAdapter->addBooleanValidator($name, $details['optional']);
                break;
            case self::RULE_NUMBER:
                $validatorAdapter->addNumberValidator($name, $details['optional'], $details['min'], $details['max']);
                break;
            case self::RULE_DATE:
                $validatorAdapter->addDateValidator($name, $details['optional']);
                break;
            default:
                throw new GaelOException('Unknown Rule');
        }
    }

    /**
     * Must return the review status for each action on review (send, delete, unlock), 
     * needs to handle backward and forward
     */
    abstract function getReviewStatus(): string;

    /**
     * Return the conclusion value, must return null if review status is not done
     */
    abstract function getReviewConclusion(): ?string;

    abstract function getAllowedKeyAndMimeTypeInvestigator(): array;

    abstract function getAllowedKeyAndMimeTypeReviewer(): array;

    abstract function getTargetLesion(): ?array;

    /**
     * Return custom data should be usefull to generate investigator form
     * Empty array by default, to be override in specific models
     */
    public function getAssociatedDataForInvestigatorForm(): array
    {
        return [];
    }

    /**
     * Return custom data should be usefull to generate review form
     * Empty array by default, to be override in specific models
     */
    public function getAssociatedDataForReviewForm(): array
    {
        return [];
    }

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
