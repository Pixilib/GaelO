<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\CenterUser;
use Faker\Generator as Faker;

$factory->define(CenterUser::class, function (Faker $faker) {
    return [
        'user_id'=>$faker->randomNumber,
        'center_code'=>$faker->randomNumber
    ];
});
