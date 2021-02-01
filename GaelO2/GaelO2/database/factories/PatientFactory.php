<?php

namespace Database\Factories;

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
            'birth_day'=>$this->faker->randomNumber,
            'birth_month'=>$this->faker->randomNumber,
            'birth_year'=>$this->faker->randomNumber,
            'registration_date'=>now(),
            'investigator_name'=>$this->faker->word,
            'center_code'=> Center::factory()->create()->code,
            'study_name'=> Study::factory()->create()->name,
            'inclusion_status'=>$this->faker->randomElement(['Included', 'Withdrawn']),
            'withdraw_reason'=>$this->faker->word,
            'withdraw_date'=>now()
        ];
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

}
