<?php

namespace App\GaelO\Adapters;

use App\GaelO\Interfaces\Adapters\ValidatorInterface;
use Respect\Validation\Validator;
use Respect\Validation\Rules\Key;

class ValidatorAdapter implements ValidatorInterface
{

    public const TYPE_STRING = "String";
    public const TYPE_NUMBER = "Number";
    public const TYPE_SET = "Set";
    public const TYPE_BOOLEAN = "Boolean";

    private Validator $validator;
    private bool $validatedForm;

    public function __construct(bool $validatedForm)
    {
        $this->validatedForm = $validatedForm;
        $this->validator = new Validator();
    }

    public function addValidatorString(string $key, bool $optional): void
    {
        if (!$this->validatedForm || $optional) $validatable = Validator::OneOf(validator::stringType(), validator::nullType());
        else $validatable = Validator::stringType();
        $validatorKey = new Key($key, $validatable, $this->validatedForm);
        $this->validator->addRule($validatorKey);
    }

    public function addValidatorInt(string $key, bool $optional, ?int $min, ?int $max): void
    {
        if (!$this->validatedForm || $optional) $validatable = Validator::OneOf(validator::intType(), validator::nullType());
        else $validatable = Validator::intType();

        if ($min != null) {
            $validatable->min($min);
        }

        if ($max != null) {
            $validatable->max($max);
        }

        $validatorKey = new Key($key, $validatable, $this->validatedForm);

        $this->validator->addRule($validatorKey);
    }

    public function addValidatorFloat(string $key, bool $optional, ?float $min, ?float $max): void
    {
        if (!$this->validatedForm || $optional) $validatable = Validator::OneOf(validator::floatType(), validator::nullType());
        else $validatable = Validator::floatType();

        if ($min != null) {
            $validatable->min($min);
        }

        if ($max != null) {
            $validatable->max($max);
        }

        $validatorKey = new Key($key, $validatable, $this->validatedForm);

        $this->validator->addRule($validatorKey);
    }

    public function addNumberValidator(string $key, bool $optional, ?float $min, ?float $max): void
    {
        if (!$this->validatedForm || $optional) $validatable = Validator::OneOf(validator::Number(), validator::nullType());
        else $validatable = Validator::Number();

        if ($min != null) {
            $validatable->min($min);
        }

        if ($max != null) {
            $validatable->max($max);
        }

        $validatorKey = new Key($key, $validatable, $this->validatedForm);

        $this->validator->addRule($validatorKey);
    }

    public function addSetValidator(string $key, array $acceptedValues, bool $optional): void
    {
        if (!$this->validatedForm || $optional) $validatable = Validator::OneOf(validator::in($acceptedValues, true), validator::nullType());
        else $validatable = Validator::in($acceptedValues, true);

        $validatorKey = new Key($key, $validatable, $this->validatedForm);
        $this->validator->addRule($validatorKey);
    }

    public function addBooleanValidator(string $key,  bool $optional): void
    {
        if (!$this->validatedForm || $optional) $validatable = Validator::OneOf(validator::boolType(), validator::nullType());
        else $validatable = Validator::boolType();

        $validatorKey = new Key($key, $validatable, $this->validatedForm);
        $this->validator->addRule($validatorKey);
    }

    public function validate(array $data): bool
    {
        return $this->validator->validate($data);
    }
}
