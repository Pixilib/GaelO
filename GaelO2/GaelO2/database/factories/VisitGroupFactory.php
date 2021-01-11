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
            'modality'=>$this->faker->randomElement(['CT', 'PT', 'MR']),
        ];
    }
}



