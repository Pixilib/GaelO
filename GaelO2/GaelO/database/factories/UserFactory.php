<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\User;
use Faker\Generator as Faker;
use Illuminate\Support\Str;

/*
|--------------------------------------------------------------------------
| Model Factories
|--------------------------------------------------------------------------
|
| This directory should contain each of the model factory definitions for
| your application. Factories provide a convenient way to generate new
| model instances for testing / seeding your application's database.
|
*/

$factory->define(User::class, function (Faker $faker) {
    
    return [
        'username' => 'administrator',
        'lastname' => 'administrator',
        'firstname' => 'administrator',
        'email' => 'administrator@administrator.fr',
        'last_password_update' => now()->subDays(100),
        'creation_date'=> now(),
        'status' => 'Activated',
        'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // password
        'center_code' => 0,
        'job_name' => 'Monitor',
        'administrator' => true,
        'remember_token' => Str::random(10)
    ];
});
