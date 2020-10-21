<?php

use App\Center;
use App\Patient;
use App\Study;
use App\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FrontTest extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->call([
            CountrySeeder::class,
            CenterSeeder::class,
            UserSeeder::class,
            PreferenceSeeder::class,
            ]);

            factory(Study::class, 10)->create();

            DB::table('studies')->insert([
                'name' => 'Study test',
                'patient_code_prefix' => '123'
            ]);

            DB::table('roles')->insert([
                'name' => 'Supervisor',
                'user_id' => '1',
                'study_name' => 'Study test',
            ]);
            DB::table('roles')->insert([
                'name' => 'Monitor',
                'user_id' => '1',
                'study_name' => 'Study test',
            ]);
            DB::table('roles')->insert([
                'name' => 'Investigator',
                'user_id' => '1',
                'study_name' => 'Study test',
            ]);
            DB::table('roles')->insert([
                'name' => 'Controller',
                'user_id' => '1',
                'study_name' => 'Study test',
            ]);
            //factory(Patient::class, 5)->create();

            DB::table('patients')->insert([
                'code'=>'123',
                'first_name'=>'truc',
                'last_name'=>'truc',
                'gender'=>'M',
                'birth_day'=>1,
                'birth_month'=>2,
                'birth_year'=>3,
                'registration_date'=>now(),
                'investigator_name'=>'bidule',
                'center_code'=>0,
                'study_name'=>'Study test',
                'withdraw'=>false,
                'withdraw_reason'=>'',
                'withdraw_date'=>now()
            ]);

            DB::table('visit_groups')->insert([
                'study_name' => 'Study test',
                'modality' => 'PT'
            ]);

            DB::table('visit_types')->insert([
                'visit_group_id' => 1,
                'name' => 'Test',
                'visit_order' => 1,
                'limit_low_days' => 1,
                'limit_up_days' => 10,
            ]);

            factory(User::class, 50)->create();


    }
}
