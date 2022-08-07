<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('users')->insert([
            'lastname' => 'administrator',
            'firstname' => 'administrator',
            'email' => 'administrator@gaelo.fr',
            'creation_date'=> now(),
            'password' => Hash::make('administrator'), // password
            'center_code' => 0,
            'job' => 'Monitor',
            'administrator' => true,
            'remember_token' => Str::random(10),
            'email_verified_at' => now(),
            'onboarding_version' => '1.0.0'
        ]);
    }
}
