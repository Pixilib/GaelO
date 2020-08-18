<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use Faker\Generator as Faker;

$factory->define(App\User::class, function (Faker $faker) {
    return [
        'lastname' => $faker->lastname,
        'firstname' => $faker->firstname,
        'username' => $faker->unique()->userName,
        'email'=> $faker->unique()->safeEmail,
        'password' => $faker->password,
        'phone' => $faker->phoneNumber,
        'last_password_update' => now(),
        'creation_date' => now(),
        'status' => $faker->randomElement(['Unconfirmed', 'Activated', 'Blocked']),
        'administrator' => $faker->randomElement([true, false]),
        'center_code' => 0,
        'job' => $faker->randomElement(['CRA', 'Monitor', 'Nuclearist','PI', 'Radiologist', 'Study nurse', 'Supervision' ]),
        'orthanc_address' => $faker->domainName,
        'orthanc_login' => $faker->userName,
        'orthanc_password' => $faker->password,
    ];
});
