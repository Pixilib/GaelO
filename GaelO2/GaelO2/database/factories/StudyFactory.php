<?php

namespace Database\Factories;

use App\Models\Study;
use Illuminate\Database\Eloquent\Factories\Factory;

class StudyFactory extends Factory
{

    protected $model = Study::class;

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
