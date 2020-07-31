<?php

use Illuminate\Database\Seeder;

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
            'email_reply_to'=>'', 
            'corporation'=>'GaelO', 
            'address'=>'GaelO.fr', 
            'parse_date_import'=>'m.d.Y', 
            'parse_country_name'=>'US', 
            'orthanc_exposed_internal_address'=>'http://orthancexposed', 
            'orthanc_exposed_internal_port'=>8042, 
            'orthanc_exposed_external_address'=>'', 
            'orthanc_exposed_external_port'=>0, 
            'orthanc_exposed_internal_login'=>'internal', 
            'orthanc_exposed_internal_password'=>'GaelO', 
            'orthanc_exposed_external_login'=>'external', 
            'orthanc_exposed_external_password'=>'GaelO', 
            'orthanc_pacs_address'=>'http://orthancpacs', 
            'orthanc_pacs_port'=>8042, 
            'orthanc_pacs_login'=>'GaelO', 
            'orthanc_pacs_password'=>'GaelO', 
            'use_smtp'=>0, 
            'smtp_host'=>'', 
            'smtp_port'=>0, 
            'smtp_user'=>'', 
            'smtp_password'=>'', 
            'smtp_secure'=>'ssl']
        );
    }
}
