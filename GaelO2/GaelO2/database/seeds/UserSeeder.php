<?php

use App\User;
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
            'username' => 'administrator',
            'lastname' => 'administrator',
            'firstname' => 'administrator',
            'email' => 'administrator@gaelo.fr',
            'last_password_update' => now()->subDays(50),
            'creation_date'=> now(),
            'status' => 'Activated',
            'password' => Hash::make('administrator'), // password
            'center_code' => 0,
            'job' => 'Monitor',
            'administrator' => true,
            'remember_token' => Str::random(10)
        ]);
        factory(User::class, 50)->create();
    }
}
