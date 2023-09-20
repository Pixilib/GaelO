<?php

namespace App\GaelO\Services\GaelOStudiesService;

use App\GaelO\Adapters\FrameworkAdapter;
use App\GaelO\Adapters\ValidatorAdapter;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Services\GaelOStudiesService\AssociatedFiles\AssociatedFile;

abstract class AbstractVisitRules
{

    const RULE_STRING = "string";
    const RULE_INT = "int";
    const RULE_NUMBER = "number";
    const RULE_SET = "set";
    const RULE_BOOLEAN = "boolean";
    const RULE_DATE = "date";

    protected array $data = [];
    protected array $uploadedFileKeys = [];
    protected bool $isLocal;
    protected bool $adjudication;
    protected string $studyName;
    protected array $visitContext;

    abstract public static function getInvestigatorValidationRules(): array;

    abstract public static function getReviewerValidationRules(): array;

    abstract public static function getReviewerAdjudicationValidationRules(): array;

    //TODO : A passer en abstract pour explicitement specifier (necessite d'updater tous les etudes en cours)
    /**
     * @return AssociatedFile[]
     */
    public static function getAssociatedFilesVisit() : array
    {
        return [];
    }

    /**
     * @return AssociatedFile[]
     */
    abstract public static function getAssociatedFilesInvestigator(): array;

    /**
     * @return AssociatedFile[]
     */
    abstract public static function getAssociatedFilesReview(): array;

    /**
     * @return AssociatedFile[]
     */
    abstract public static function getAssociatedFilesAdjudication(): array;

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

    public function setUploadedFileKeys(array $uploadedFileKeys)
    {
        $this->uploadedFileKeys = $uploadedFileKeys;
    }

    public function setLocalForm(bool $isLocal)
    {
        $this->isLocal = $isLocal;
    }

    public function setAdjudication(bool $adjudication)
    {
        $this->adjudication = $adjudication;
    }

    /**
     *  @param AssociatedFile[]
     */
    protected function areAllAssociatedFilesUploaded(array $associatedFiles): bool
    {
        $mandatoryKeys = array_map(function (AssociatedFile $associatedFile) {
            if ($associatedFile->mandatory) return $associatedFile->key;
            else return null;
        }, $associatedFiles);
        $mandatoryKeys = array_filter($mandatoryKeys);
        $associatedFileCompleted = sizeof(array_intersect($this->uploadedFileKeys, $mandatoryKeys)) === sizeof($mandatoryKeys);
        return $associatedFileCompleted;
    }

    protected function areAssociatedFilesInvestigatorComplete(): bool
    {
        $investigatorAssociatedFiles = $this->getAssociatedFilesInvestigator();
        return $this->areAllAssociatedFilesUploaded($investigatorAssociatedFiles);
    }

    protected function areAssociatedFilesReviewerComplete(): bool
    {
        $reviewerAssociatedFiles = $this->getAssociatedFilesReview();
        return $this->areAllAssociatedFilesUploaded($reviewerAssociatedFiles);
    }

    protected function areAssociatedFilesAdjudicationComplete(): bool
    {
        $adjudicationAssociatedFiles = $this->getAssociatedFilesAdjudication();
        return $this->areAllAssociatedFilesUploaded($adjudicationAssociatedFiles);
    }

    public function checkInvestigatorFormValidity(bool $validated): bool
    {

        $validatorAdapter = new ValidatorAdapter($validated);
        $investigatorsRules = $this::getInvestigatorValidationRules();
        $this->fillValidator($investigatorsRules, $validatorAdapter);
        $fileCheck = $validated ? $this->areAssociatedFilesInvestigatorComplete() : true;
        return $validatorAdapter->validate($this->data) && $fileCheck;
    }

    public function checkReviewFormValidity(bool $validated): bool
    {
        $validatorAdapter = new ValidatorAdapter($validated);
        $reviewerRules = [];
        $associatedFileCheck = false;

        if ($this->adjudication) {
            $reviewerRules = $this->getReviewerAdjudicationValidationRules();
            $associatedFileCheck = $validated ? $this->areAssociatedFilesAdjudicationComplete() : true;
        } else {
            $reviewerRules = $this->getReviewerValidationRules();
            $associatedFileCheck = $validated ? $this->areAssociatedFilesReviewerComplete() : true;
        }

        $this->fillValidator($reviewerRules, $validatorAdapter);
        return $validatorAdapter->validate($this->data) && $associatedFileCheck;
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
