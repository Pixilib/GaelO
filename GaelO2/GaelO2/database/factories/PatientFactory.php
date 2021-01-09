<?php

namespace Database\Factories;

use App\Model\Patient;
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
            'center_code'=>$this->faker->unique()->randomNumber,
            'study_name'=>$this->faker->word,
            'inclusion_status'=>$this->faker->randomElement(['Included', 'Withdrawn']),
            'withdraw_reason'=>$this->faker->word,
            'withdraw_date'=>now()
        ];
    }
}
