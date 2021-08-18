<?php

namespace Database\Factories;

use App\GaelO\Util;
use App\Models\Center;
use App\Models\Patient;
use App\Models\Study;
use Illuminate\Database\Eloquent\Factories\Factory;

class PatientFactory extends Factory
{

    protected $model = Patient::class;

    public function definition()
    {
        return [
            'code'=>$this->faker->unique()->randomNumber,
            'firstname'=>strtoupper($this->faker->lexify('?')),
            'lastname'=>strtoupper($this->faker->lexify('?')),
            'gender'=>$this->faker->randomElement(['M', 'F']),
            'birth_day'=>$this->faker->numberBetween(1,30),
            'birth_month'=>$this->faker->numberBetween(1,12),
            'birth_year'=>$this->faker->numberBetween(1900, 2020),
            'registration_date'=>Util::now(),
            'investigator_name'=>$this->faker->word,
            'center_code'=> Center::factory()->create()->code,
            'study_name'=> Study::factory()->create()->name,
            'inclusion_status'=>$this->faker->randomElement(['Included', 'Withdrawn']),
            'withdraw_reason'=>$this->faker->word,
            'withdraw_date'=>Util::now()
        ];
    }

    public function code(int $code){

        return $this->state(function (array $attributes) use($code) {
            return [
                'code' => $code,
            ];
        });

    }

    public function studyName(string $studyName){

        return $this->state(function (array $attributes) use($studyName) {
            return [
                'study_name' => $studyName,
            ];
        });

    }

    public function centerCode(int $centerCode){

        return $this->state(function (array $attributes) use($centerCode) {
            return [
                'center_code' => $centerCode,
            ];
        });

    }

    public function inclusionStatus(string $inclusionStatus){

        return $this->state(function (array $attributes) use($inclusionStatus) {
            return [
                'inclusion_status' => $inclusionStatus,
            ];
        });

    }

    public function investigatorName(string $investigatorName){

        return $this->state(function (array $attributes) use($investigatorName) {
            return [
                'investigator_name' => $investigatorName,
            ];
        });

    }

}
