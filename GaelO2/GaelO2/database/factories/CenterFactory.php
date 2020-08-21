<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Center;
use Faker\Generator as Faker;

$factory->define(Center::class, function (Faker $faker) {
    return [
        'code' => ($faker->unique()->randomNumber()+1),
        'name' => $faker->unique()->word,
        'country_code' => 'FR'
    ];
});
