<?php

namespace Database\Factories;

use App\Model\Center;
use Illuminate\Database\Eloquent\Factories\Factory;

class CenterFactory extends Factory
{

    protected $model = Center::class;

    public function definition()
    {
        return [
            'code' => ($this->faker->unique()->randomNumber()+1),
            'name' => $this->faker->unique()->word,
            'country_code' => 'FR'
        ];
    }
}
