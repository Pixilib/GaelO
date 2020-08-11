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
        $this->call([
            CountrySeeder::class, 
            CenterSeeder::class,
            UserSeeder::class,
            StudySeeder::class,
            RoleSeeder::class,
            PreferenceSeeder::class
        ]);
    }
}
