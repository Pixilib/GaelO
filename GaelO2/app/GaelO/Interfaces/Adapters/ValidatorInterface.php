<?php

namespace App\GaelO\Interfaces\Adapters;

Interface ValidatorInterface {
    public function addValidatorString(string $key, bool $optional) : void;
    public function addValidatorInt(string $key, bool $optional, ?int $min, ?int $max) : void;
    public function addNumberValidator(string $key, bool $optional, ?float $min, ?float $max): void;
    public function addSetValidator(string $key, array $acceptedValues, bool $optional) : void;
    public function addBooleanValidator(string $key,  bool $optional): void;
    public function validate(array $data) : bool ;
}
