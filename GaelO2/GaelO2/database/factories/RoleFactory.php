<?php

namespace Database\Factories;

use App\Model\Role;
use Illuminate\Database\Eloquent\Factories\Factory;

class RoleFactory extends Factory
{

    protected $model = Role::class;

    public function definition()
    {
        return [
            'name'=> $this->faker->randomElement(['Investigator', 'Monitor', 'Supervisor', 'Reviewer']),
            'user_id'=> $this->faker->unique()->randomNumber,
            'study_name'=> $this->faker->word
        ];
    }
}
