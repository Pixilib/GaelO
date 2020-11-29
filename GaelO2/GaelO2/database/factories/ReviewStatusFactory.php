<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\ReviewStatus;
use Faker\Generator as Faker;

$factory->define(ReviewStatus::class, function (Faker $faker) {
    return [
        'visit_id'=>$faker->randomNumber,
        'study_name'=>$faker->word,
        'review_available'=>$faker->randomElement([true, false]),
        'review_status'=> $faker->randomElement(['Not Done', 'Not Needed', 'Ongoing','Wait Adjudication','Done']),
        'review_conclusion_value'=>$faker->word,
        'review_conclusion_date'=>now()

    ];
});
