<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PreferenceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('preferences')->insert(
            ['patient_code_length'=>14, 
            'name'=>'GaelO', 
            'admin_email'=>'administrator@gaelo.fr', 
            'email_reply_to'=>'administrator@gaelo.fr', 
            'corporation'=>'GaelO', 
            'address'=>'GaelO.fr', 
            'parse_date_import' =>'m.d.Y',
            'parse_country_name' => 'US'
            ]
        );
    }
}
