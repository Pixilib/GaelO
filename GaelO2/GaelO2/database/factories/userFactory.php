<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use Faker\Generator as Faker;

$factory->define(App\User::class, function (Faker $faker) {
    return [
        'lastname' => $faker->lastname,
        'firstname' => $faker->firstname,
        'username' => $faker->unique()->userName,
        'email'=> $faker->unique()->safeMail,
        'password' => $faker->password,
        'phone' => $faker->phoneNumber,
        'last_password_update' => now(),
        'creation_date' => now(),
        'status' => $faker->randomFrom(['Unconfirmed', 'Activated', 'Blocked']),
        'administrator' => $faker->randomFrom([true, false]),
        'center_code' => $faker->randomDigit,
        'job' => $faker->randomFrom(['CRA', 'Monitor', 'Nuclearist','PI', 'Radiologist', 'Study nurse', 'Supervision' ]),
        'orthanc_address' => $faker->domainName,
        'orthanc_login' => $faker->userName,
        'orthanc_password' => $faker->password,
    ];
});
