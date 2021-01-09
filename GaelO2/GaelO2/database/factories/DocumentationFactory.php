<?php

namespace Database\Factories;

use App\Models\Documentation;
use Illuminate\Database\Eloquent\Factories\Factory;

class DocumentationFactory extends Factory
{

    protected $model = Documentation::class;

    public function definition()
    {
        return [
            'id'=>$this->faker->unique()->randomNumber,
            'name'=>$this->faker->unique()->word,
            'document_date'=>now(),
            'study_name'=>$this->faker->word,
            'version'=>$this->faker->word,
            'investigator'=> $this->faker->randomElement([true, false]),
            'controller'=> $this->faker->randomElement([true, false]),
            'monitor'=> $this->faker->randomElement([true, false]),
            'reviewer'=> $this->faker->randomElement([true, false]),
            'path'=> $this->faker->word
        ];
    }
}
