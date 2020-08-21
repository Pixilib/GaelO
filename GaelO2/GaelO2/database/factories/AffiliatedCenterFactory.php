<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\AffiliatedCenter;
use Faker\Generator as Faker;

$factory->define(AffiliatedCenter::class, function (Faker $faker) {
    return [
        'user_id'=>$faker->randomNumber,
        'center_code'=>$faker->randomNumber
    ];
});
