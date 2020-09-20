<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\VisitType;
use Faker\Generator as Faker;

$factory->define(VisitType::class, function (Faker $faker) {
    return [
        'visit_group_id'=>  $faker->unique()->randomNumber,
        'name'=>  $faker->word,
        'visit_order'=>  $faker->unique()->randomNumber,
        'local_form_needed'=>$faker->randomElement([true, false]),
        'qc_needed'=>$faker->randomElement([true, false]),
        'review_needed'=>$faker->randomElement([true, false]),
        'optional'=>$faker->randomElement([true, false]),
        'limit_low_days'=>  $faker->randomNumber,
        'limit_up_days'=> $faker->randomNumber,
        'anon_profile'=>$faker->randomElement( ['Default', 'Full'])
    ];
});
