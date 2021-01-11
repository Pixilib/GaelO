<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class StudyFactory extends Factory
{

    public function definition()
    {
        return [
            'name' => $this->faker->unique()->word,
            'patient_code_prefix' => $this->faker->randomNumber(5),
        ];
    }

    public function name(){

        return $this->state(function (array $attributes) {
            return [
                'name' => true,
            ];
        });
    }
}
