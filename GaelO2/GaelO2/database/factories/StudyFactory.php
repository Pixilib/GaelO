<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class StudyFactory extends Factory
{

    public function definition()
    {
        return [
            'name' => $this->faker->unique()->regexify('[A-Z0-9]{20}'),
            'code' => $this->faker->unique()->randomNumber(5),
            'patient_number_length' => $this->faker->randomNumber(5)
        ];
    }

    public function name(String $name){

        return $this->state(function (array $attributes) use($name) {
            return [
                'name' => $name
            ];
        });
    }

    public function patientNumberLength(int $length){

        return $this->state(function (array $attributes) use($length) {
            return [
                'patient_number_length' => $length
            ];
        });
    }

    public function code(int $code){

        return $this->state(function (array $attributes) use($code) {
            return [
                'code' => $code,
            ];
        });

    }
}
