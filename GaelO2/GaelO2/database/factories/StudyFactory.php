<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Study;
use Faker\Generator as Faker;

$factory->define(Study::class, function (Faker $faker) {
    return [
        'name' => $faker->unique()->word,
        'patient_code_prefix' => $faker->randomNumber(5),
    ];
});
