<?php

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call([CountrySeeder::class, 
        JobSeeder::class,
        CenterSeeder::class,
        UserSeeder::class,
        PreferenceSeeder::class,
        StateSeeder::class,
        SelectRoleSeeder::class]);
    }
}
