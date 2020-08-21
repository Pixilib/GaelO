<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Role;
use Faker\Generator as Faker;

$factory->define(Role::class, function (Faker $faker) {
    return [
        'name'=> $faker->randomElement(['Investigator', 'Monitor', 'Supervisor', 'Reviewer']),
        'user_id'=> $faker->unique()->randomNumber,
        'study_name'=> $faker->word
    ];
});
