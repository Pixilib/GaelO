<?php

namespace Tests\Unit\TestAdapters;

use App\GaelO\Adapters\ValidatorAdapter;
use Tests\TestCase;


class ValidatorAdapterTest extends TestCase
{
    private ValidatorAdapter $validatorAdapter;


    protected function setUp(): void
    {
        parent::setUp();
        $this->validatorAdapter = new ValidatorAdapter(true);
    }

    public function testAddMendatoryStringValidation()
    {
        $this->validatorAdapter->addValidatorString('name', false);
        $result = $this->validatorAdapter->validate(['name' => 'yes']);
        $this->assertTrue($result);
        $result = $this->validatorAdapter->validate(['name' => 1]);
        $this->assertFalse($result);
        $result = $this->validatorAdapter->validate([]);
        $this->assertFalse($result);
    }

    public function testAddOptionalStringValidation()
    {
        $this->validatorAdapter->addValidatorString('name', true);
        $result = $this->validatorAdapter->validate(['name' => 'yes']);
        $this->assertTrue($result);
        $result = $this->validatorAdapter->validate(['name' => null]);
        $this->assertTrue($result);
        $result = $this->validatorAdapter->validate(['name' => 1]);
        $this->assertFalse($result);
    }

    public function testAddMendatoryIntValidation()
    {
        $this->validatorAdapter->addValidatorInt('age', false, 5, 50);
        $result = $this->validatorAdapter->validate(['age' => 30]);
        $this->assertTrue($result);
        $result = $this->validatorAdapter->validate(['age' => 60]);
        $this->assertFalse($result);
        $result = $this->validatorAdapter->validate(['age' => 30.5]);
        $this->assertFalse($result);
        $result = $this->validatorAdapter->validate(['age' => '50']);
        $this->assertFalse($result);
    }

    public function testAddOptionalIntValidation()
    {
        $this->validatorAdapter->addValidatorInt('age', true, 5, 50);
        $result = $this->validatorAdapter->validate(['age' => null]);
        $this->assertTrue($result);
        $result = $this->validatorAdapter->validate(['age' => 60]);
        $this->assertFalse($result);
    }

    public function testAddMendatorySetValidation()
    {
        $this->validatorAdapter->addSetValidator('lugano', ['CR', 'PR', 'NMR', 'PD'], false);
        $result = $this->validatorAdapter->validate(['lugano' => 'CR']);
        $this->assertTrue($result);
        $result = $this->validatorAdapter->validate(['lugano' => 'Complete']);
        $this->assertFalse($result);
        $result = $this->validatorAdapter->validate(['lugano' => 'CR2']);
        $this->assertFalse($result);
    }

    public function testAddOptionalSetValidation()
    {
        $this->validatorAdapter->addSetValidator('lugano', ['CR', 'PR', 'NMR', 'PD'], true);
        $result = $this->validatorAdapter->validate(['lugano' => null]);
        $this->assertTrue($result);
        $result = $this->validatorAdapter->validate(['lugano' => 'CR2']);
        $this->assertFalse($result);
    }

    public function testAddMandatoryDateValidation(){
        $this->validatorAdapter->addDateValidator('infectionDate', false);
        $result = $this->validatorAdapter->validate(['infectionDate' => '2022-12-30']);
        $this->assertTrue($result);
        $result = $this->validatorAdapter->validate(['infectionDate' => '2022-50-98']);
        $this->assertFalse($result);
    }

    public function testAddOptionalDateValidation(){
        $this->validatorAdapter->addDateValidator('infectionDate', true);
        $result = $this->validatorAdapter->validate(['infectionDate' => '2022-12-30']);
        $this->assertTrue($result);
        $result = $this->validatorAdapter->validate(['infectionDate' => null]);
        $this->assertTrue($result);
    }
}
