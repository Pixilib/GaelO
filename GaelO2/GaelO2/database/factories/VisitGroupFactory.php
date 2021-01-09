<?php

namespace Database\Factories;

use App\Model\VisitGroup;
use Illuminate\Database\Eloquent\Factories\Factory;

class VisitGroupFactory extends Factory
{

    protected $model = VisitGroup::class;

    public function definition()
    {
        return [
            'study_name'=> $this->faker->unique()->word,
            'modality'=>$this->faker->randomElement(['CT', 'PT', 'MR']),
        ];
    }
}



