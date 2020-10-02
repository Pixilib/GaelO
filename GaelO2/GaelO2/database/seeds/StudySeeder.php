<?php

use App\Study;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

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
            'name' => 'Study test',
            'patient_code_prefix' => '123'
        ]);
        factory(Study::class, 10)->create();
    }
}
