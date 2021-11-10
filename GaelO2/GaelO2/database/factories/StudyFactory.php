<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class StudyFactory extends Factory
{

    public function definition()
    {
        return [
            'name' => $this->faker->unique()->regexify('[A-Z0-9]{20}'),
            'code' => $this->faker->randomNumber(5),
        ];
    }

    public function name(String $name){

        return $this->state(function (array $attributes) use($name) {
            return [
                'name' => $name
            ];
        });
    }

    public function patientCodePrefix(int $code){

        return $this->state(function (array $attributes) use($code) {
            return [
                'code' => $code,
            ];
        });

    }
}
