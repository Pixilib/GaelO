<?php

namespace Database\Factories;

use App\Models\Study;
use Illuminate\Database\Eloquent\Factories\Factory;

class VisitGroupFactory extends Factory
{

    public function definition()
    {
        return [
            'study_name'=> Study::factory()->create()->name,
            'name' => $this->faker->unique()->word,
            'modality'=>'CT',
        ];
    }

    public function studyName(string $studyName){

        return $this->state(function (array $attributes) use ($studyName) {
            return [
                'study_name' => $studyName
            ];
        });
    }

    public function name(string $name){
        return $this->state(function (array $attributes) use ($name) {
            return [
                'name' => $name
            ];
        });
    }

    public function modality(string $modality){

        return $this->state(function (array $attributes) use ($modality) {
            return [
                'modality' => $modality
            ];
        });
    }
}



