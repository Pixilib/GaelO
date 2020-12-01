<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Review;
use Faker\Generator as Faker;

$factory->define(Review::class, function (Faker $faker) {
    return [
        'id'=>$faker->unique()->randomNumber,
        'study_name'=>$faker->word,
        'visit_id'=>$faker->randomNumber,
        'user_id'=>$faker->randomNumber,
        'review_date'=> now(),
        'validated'=>$faker->randomElement([true, false]),
        'adjudication'=>$faker->randomElement([true, false]),
        'sent_files'=>json_encode([]),
        'review_data'=>json_encode(['item1'=>'a', 'item2'=>5])
    ];

});
