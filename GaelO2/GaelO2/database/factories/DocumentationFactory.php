<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Documentation;
use Faker\Generator as Faker;

$factory->define(Documentation::class, function (Faker $faker) {
    return [
        'id'=>$faker->unique()->randomNumber,
        'name'=>$faker->unique()->word,
        'document_date'=>now(),
        'study_name'=>$faker->word,
        'version'=>$faker->word,
        'investigator'=> $faker->randomElement([true, false]),
        'controller'=> $faker->randomElement([true, false]),
        'monitor'=> $faker->randomElement([true, false]),
        'reviewer'=> $faker->randomElement([true, false]),
        'path'=> $faker->word
    ];
});
