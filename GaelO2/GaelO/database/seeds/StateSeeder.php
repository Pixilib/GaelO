<?php

use Illuminate\Database\Seeder;

class StateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('states')->insert(array(
            array('quality_state' => 'Accepted'),
            array('quality_state' => 'Corrective Action Asked'),
            array('quality_state' => 'Not Done'),
            array('quality_state' => 'Refused'),
            array('quality_state' => 'Wait Definitive Conclusion')
        ));
    }
}
