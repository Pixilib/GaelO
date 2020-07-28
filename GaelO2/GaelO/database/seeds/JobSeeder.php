<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class JobSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('jobs')->insert(
            array(
                array('name' => 'CRA'),
                array('name' => 'Monitor'),
                array('name' => 'Nuclearist'),
                array('name' => 'PI'),
                array('name' => 'Radiologist'),
                array('name' => 'Study nurse'),
                array('name' => 'Supervision')
            )
        );
    }
}
