<?php

namespace App\GaelO\Adapters;

use App\GaelO\Interfaces\Adapters\ValidatorInterface;
use App\Rules\BooleanType;
use App\Rules\NumberType;
use App\Rules\StringType;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ValidatorAdapter implements ValidatorInterface
{
    private array $validationRules;
    private bool $validatedForm;

    public function __construct(bool $validatedForm)
    {
        $this->validatedForm = $validatedForm;
    }

    public function addValidatorString(string $key, bool $optional): void
    {
        $rules = [new StringType];
        $rules[] = "string";
        if ($optional || !$this->validatedForm) {
            $rules[] = 'nullable';
        } else $rules[] = 'required';

        $this->validationRules[$key] = $rules;
    }

    public function addValidatorInt(string $key, bool $optional, ?int $min, ?int $max): void
    {
        $rules = [new NumberType, "integer", "numeric"];

        if ($optional || !$this->validatedForm) {
            $rules[] = 'nullable';
        } else {
            $rules[] = 'required';
        }

        if ($min != null) {
            $rules[] = "min:" . $min;
        }

        if ($max != null) {
            $rules[] = "max:" . $max;
        }


        $this->validationRules[$key] = $rules;
    }

    public function addNumberValidator(string $key, bool $optional, ?float $min, ?float $max): void
    {
        $rules = [new NumberType, "numeric"];

        if ($optional || !$this->validatedForm) {
            $rules[] = 'nullable';
        } else {
            $rules[] = 'required';
        }

        if ($min != null) {
            $rules[] = "min:" . $min;
        }

        if ($max != null) {
            $rules[] = "max:" . $max;
        }

        $this->validationRules[$key] = $rules;
    }

    public function addSetValidator(string $key, array $acceptedValues, bool $optional): void
    {

        $rules = [Rule::in($acceptedValues)];

        if ($optional || !$this->validatedForm) {
            $rules[] = 'nullable';
        } else {
            $rules[] = 'required';
        }

        $this->validationRules[$key] = $rules;
    }

    public function addBooleanValidator(string $key,  bool $optional): void
    {
        $rules = [new BooleanType, "boolean"];
        if ($optional || !$this->validatedForm) {
            $rules[] = 'nullable';
        } else {
            $rules[] = 'required';
        }

        $this->validationRules[$key] = $rules;
    }

    public function addDateValidator(string $key, bool $optional): void
    {
        $rules = ["date"];

        if ($optional || !$this->validatedForm) {
            $rules[] = 'nullable';
        } else {
            $rules[] = 'required';
        }

        $this->validationRules[$key] = $rules;
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
