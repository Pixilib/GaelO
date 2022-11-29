<?php

namespace App\GaelO\Services\GaelOStudiesService;

use App\GaelO\Adapters\FrameworkAdapter;
use App\GaelO\Adapters\ValidatorAdapter;
use App\GaelO\Exceptions\GaelOException;

abstract class AbstractVisitRules
{

    const RULE_STRING = "string";
    const RULE_INT = "int";
    const RULE_NUMBER = "number";
    const RULE_SET = "set";
    const RULE_BOOLEAN = "boolean";
    const RULE_DATE = "date";

    protected array $data = [];
    protected bool $isLocal;
    protected bool $adjudication;
    protected string $studyName;
    protected array $visitContext;

    abstract public static function getInvestigatorValidationRules(): array;

    abstract public static function getReviewerValidationRules(): array;

    abstract public static function getReviewerAdjudicationValidationRules(): array;

    abstract public static function getAllowedKeyAndMimeTypeInvestigator(): array;

    abstract public static function getAllowedKeyAndMimeTypeReviewer(): array;

    abstract public static function getAllowedKeyAndMimeTypeAdjudication(): array;

    public function getInvestigatorInputNames(): array
    {
        $rules = $this::getInvestigatorValidationRules();
        return array_unique(array_keys($rules));
    }

    public function getReviewerInputNames(): array
    {
        $rules = $this::getReviewerValidationRules();
        $adjudicationRules = $this::getReviewerAdjudicationValidationRules();
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

    public function setLocalForm(bool $isLocal)
    {
        $this->isLocal = $isLocal;
    }

    public function setAdjudication(bool $adjudication)
    {
        $this->adjudication = $adjudication;
    }

    public function checkInvestigatorFormValidity(bool $validated): bool
    {

        $validatorAdapter = new ValidatorAdapter($validated);
        $investigatorsRules = $this::getInvestigatorValidationRules();
        $this->fillValidator($investigatorsRules, $validatorAdapter);
        return $validatorAdapter->validate($this->data);
    }

    public function checkReviewFormValidity(bool $validated): bool
    {
        $validatorAdapter = new ValidatorAdapter($validated);
        $reviewerRules = [];

        if ($this->adjudication) {
            $reviewerRules = $this::getReviewerAdjudicationValidationRules();
        } else {
            $reviewerRules = $this::getReviewerValidationRules();
        }

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
                $validatorAdapter->addValidatorInt($name, $details['optional'], $details['min'] ?? null, $details['max'] ?? null);
                break;
            case self::RULE_SET:
                $validatorAdapter->addSetValidator($name, $details['values'], $details['optional']);
                break;
            case self::RULE_BOOLEAN:
                $validatorAdapter->addBooleanValidator($name, $details['optional']);
                break;
            case self::RULE_NUMBER:
                $validatorAdapter->addNumberValidator($name, $details['optional'], $details['min'] ?? null, $details['max'] ?? null);
                break;
            case self::RULE_DATE:
                $validatorAdapter->addDateValidator($name, $details['optional']);
                break;
            default:
                throw new GaelOException('Unknown Rule');
        }
    }

    abstract static public function getVisitDecisionClass(): string;

    public function getVisitDecisionObject(): AbstractVisitDecisions
    {
        $className =  $this->getVisitDecisionClass();
        $visitDecisionObject = FrameworkAdapter::make($className);
        $visitDecisionObject->setVisitContext($this->visitContext);
        $visitDecisionObject->setFormData($this->data);
        $visitDecisionObject->setLocalForm($this->isLocal);
        if (isset($this->adjudication)) $visitDecisionObject->setAdjudication($this->adjudication);
        $visitDecisionObject->setStudyName($this->studyName);
        return $visitDecisionObject;
    }
}
