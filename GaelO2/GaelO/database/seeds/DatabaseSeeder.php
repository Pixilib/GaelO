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
        $this->call(CountrySeeder::class);
        $this->call(JobSeeder::class);
        $this->call(CenterSeeder::class);
        $this->call(UserSeeder::class);
        $this->call(PreferenceSeeder::class);
        $this->call(StateSeeder::class);
        $this->call(SelectRoleSeeder::class);
    }
}
