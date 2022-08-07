<?php

namespace App\GaelO\Adapters;

use App\GaelO\Interfaces\Adapters\ValidatorInterface;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ValidatorAdapter implements ValidatorInterface
{

    public const TYPE_STRING = "String";
    public const TYPE_NUMBER = "Number";
    public const TYPE_SET = "Set";
    public const TYPE_BOOLEAN = "Boolean";

    private array $validationRules;
    private bool $validatedForm;

    public function __construct(bool $validatedForm)
    {
        $this->validatedForm = $validatedForm;
    }

    private function buildRuleString(array $rules): string
    {
        return implode('|', $rules);
    }

    public function addValidatorString(string $key, bool $optional): void
    {
        $rules = [];
        $rules[] = "string";
        if ($optional || !$this->validatedForm) $rules[] = 'nullable';
        else $rules[] = 'required';

        $this->validationRules[$key] = $this->buildRuleString($rules);
    }

    public function addValidatorInt(string $key, bool $optional, ?int $min, ?int $max): void
    {
        $rules = [];
        $rules[] = "integer";
        $rules[] = "numeric";
        if ($optional || !$this->validatedForm) $rules[] = 'nullable';
        else $rules[] = 'required';

        if ($min != null) {
            $rules[] = "min:" . $min;
        }

        if ($max != null) {
            $rules[] = "max:" . $max;
        }


        $this->validationRules[$key] = $this->buildRuleString($rules);
    }

    public function addNumberValidator(string $key, bool $optional, ?float $min, ?float $max): void
    {
        $rules = [];
        $rules[] = "numeric";
        if ($optional || !$this->validatedForm) $rules[] = 'nullable';
        else $rules[] = 'required';

        if ($min != null) {
            $rules[] = "min:" . $min;
        }

        if ($max != null) {
            $rules[] = "max:" . $max;
        }

        $this->validationRules[$key] = $this->buildRuleString($rules);
    }

    public function addSetValidator(string $key, array $acceptedValues, bool $optional): void
    {

        $rules = [];
        if ($optional || !$this->validatedForm) $rules[] = 'nullable';
        else $rules[] = 'required';

        $this->validationRules[$key] = [
            $this->buildRuleString($rules),
            Rule::in($acceptedValues)
        ];
    }

    public function addBooleanValidator(string $key,  bool $optional): void
    {
        $rules = [];
        $rules[] = "boolean";
        if ($optional || !$this->validatedForm) $rules[] = 'nullable';
        else $rules[] = 'required';

        $this->validationRules[$key] = $this->buildRuleString($rules);
    }

    public function validate(array $data): bool
    {
        $validator = Validator::make($data, $this->validationRules);
        if ($validator->fails()) {
            return false;
        } else {
            return true;
        }
    }
}
