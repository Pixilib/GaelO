<?php

namespace Tests\Unit\TestAdapters;

use App\GaelO\Adapters\ValidatorAdapter;
use App\GaelO\Exceptions\GaelOBadRequestException;
use Tests\TestCase;


class ValidatorAdapterTest extends TestCase
{
    private ValidatorAdapter $validatorAdapter;


    protected function setUp(): void
    {
        parent::setUp();
        $this->validatorAdapter = new ValidatorAdapter(true);
    }

    public function testMendatoryStringValidation()
    {
        $this->validatorAdapter->addValidatorString('name', false);
        $result = $this->validatorAdapter->validate(['name' => 'yes']);
        $this->assertTrue($result);
    }

    public function testIntInsteadOfStringValidation()
    {
        $this->validatorAdapter->addValidatorString('name', false);
        $this->expectException(GaelOBadRequestException::class);
        $this->validatorAdapter->validate(['name' => 1]);
    }

    public function testMissingMendatoryStringValidation()
    {
        $this->validatorAdapter->addValidatorString('name', false);
        $this->expectException(GaelOBadRequestException::class);
        $this->validatorAdapter->validate([]);
    }

    public function testAddOptionalStringValidation()
    {
        $this->validatorAdapter->addValidatorString('name', true);
        $result = $this->validatorAdapter->validate(['name' => 'yes']);
        $this->assertTrue($result);
        $result = $this->validatorAdapter->validate(['name' => null]);
        $this->assertTrue($result);
    }

    public function testIntInsteadOfOptionalStringValidation()
    {
        $this->validatorAdapter->addValidatorString('name', false);
        $this->expectException(GaelOBadRequestException::class);
        $this->validatorAdapter->validate(['name' => 1]);
    }

    public function testAddMendatoryIntValidation()
    {
        $this->validatorAdapter->addValidatorInt('age', false, 5, 50);
        $result = $this->validatorAdapter->validate(['age' => 30]);
        $this->assertTrue($result);
    }

    public function testIntOutOfLimitValidation()
    {
        $this->validatorAdapter->addValidatorInt('age', false, 5, 50);
        $this->expectException(GaelOBadRequestException::class);
        $this->validatorAdapter->validate(['age' => 60]);
    }

    public function testDecimalInsteadOfIntValidation()
    {
        $this->validatorAdapter->addValidatorInt('age', false, 5, 50);
        $this->expectException(GaelOBadRequestException::class);
        $result = $this->validatorAdapter->validate(['age' => 30.5]);
    }

    public function testStringInsteadOfIntValidation()
    {
        $this->validatorAdapter->addValidatorInt('age', false, 5, 50);
        $this->expectException(GaelOBadRequestException::class);
        $this->validatorAdapter->validate(['age' => '50']);
    }

    public function testAddOptionalIntValidation()
    {
        $this->validatorAdapter->addValidatorInt('age', true, 5, 50);
        $result = $this->validatorAdapter->validate(['age' => null]);
        $this->assertTrue($result);

        //Even if option should no be able to send erroneous data
        $this->expectException(GaelOBadRequestException::class);
        $result = $this->validatorAdapter->validate(['age' => 60]);
        $this->assertFalse($result);
    }

    public function testAddMendatorySetValidation()
    {
        $this->validatorAdapter->addSetValidator('lugano', ['CR', 'PR', 'NMR', 'PD'], false);
        $result = $this->validatorAdapter->validate(['lugano' => 'CR']);
        $this->assertTrue($result);
    }

    public function testValueOutOfSetValidation()
    {
        $this->validatorAdapter->addSetValidator('lugano', ['CR', 'PR', 'NMR', 'PD'], false);
        $this->expectException(GaelOBadRequestException::class);
        $this->validatorAdapter->validate(['lugano' => 'CR2']);
    }


    public function testAddOptionalSetValidation()
    {
        $this->validatorAdapter->addSetValidator('lugano', ['CR', 'PR', 'NMR', 'PD'], true);
        $result = $this->validatorAdapter->validate(['lugano' => null]);
        $this->assertTrue($result);
        $result = $this->validatorAdapter->validate([]);
        $this->assertTrue($result);

        //Even if option should no be able to send erroneous data
        $this->expectException(GaelOBadRequestException::class);
        $result = $this->validatorAdapter->validate(['lugano' => 'CR2']);
        $this->assertTrue($result);
    }

    public function testAddMandatoryDateValidation()
    {
        $this->validatorAdapter->addDateValidator('infectionDate', false);
        $result = $this->validatorAdapter->validate(['infectionDate' => '2022-12-30']);
        $this->assertTrue($result);

        $this->expectException(GaelOBadRequestException::class);
        $this->validatorAdapter->validate(['infectionDate' => '2022-50-98']);
    }

    public function testAddOptionalDateValidation()
    {
        $this->validatorAdapter->addDateValidator('infectionDate', true);
        $result = $this->validatorAdapter->validate(['infectionDate' => '2022-12-30']);
        $this->assertTrue($result);

        $result = $this->validatorAdapter->validate(['infectionDate' => null]);
        $this->assertTrue($result);

        //Even if option should no be able to send erroneous data
        $this->expectException(GaelOBadRequestException::class);
        $this->validatorAdapter->validate(['infectionDate' => 'here']);
    }
}
