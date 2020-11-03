<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\ReviewStatus;
use Faker\Generator as Faker;

$factory->define(ReviewStatus::class, function (Faker $faker) {
    return [
        'visit_id'=>$faker->unique()->randomNumber,
        'study_name'=>$faker->word,
        'review_available'=>$faker->randomElement([true, false])
    ];
});
