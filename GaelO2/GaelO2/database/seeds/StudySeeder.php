<?php

use Illuminate\Database\Seeder;

class StudySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('studies')->insert([
            'name' => 'Test',
            'patient_code_prefix' => 'test'
        ]);
    }
}
