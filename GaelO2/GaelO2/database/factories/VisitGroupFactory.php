<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\VisitGroup;
use Faker\Generator as Faker;

$factory->define(VisitGroup::class, function (Faker $faker) {
    return [
        'study_name'=> $faker->unique()->word,
        'modality'=>$faker->randomElement(['CT', 'PT', 'MR']),
    ];
});
