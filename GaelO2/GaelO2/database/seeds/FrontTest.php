<?php

use App\Center;
use App\Patient;
use App\Study;
use App\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

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
            PreferenceSeeder::class,
        ]);

        //Make Admin default password valid
        DB::table('users')->insert([
            'username' => 'administrator',
            'lastname' => 'administrator',
            'firstname' => 'administrator',
            'email' => 'administrator@gaelo.fr',
            'last_password_update' => now(),
            'creation_date'=> now(),
            'status' => 'Activated',
            'password' => Hash::make('administrator'), // password
            'center_code' => 0,
            'job' => 'Monitor',
            'administrator' => true,
            'remember_token' => Str::random(10)
        ]);

        factory(Study::class, 5)->create();

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


        factory(Patient::class, 5)->create(['investigator_name' => 'administrator', 'study_name' => 'Study test', 'center_code' => 0]);

        DB::table('visit_groups')->insert([
            'study_name' => 'Study test',
            'modality' => 'PT'
        ]);
        DB::table('visit_groups')->insert([
            'study_name' => 'Study test',
            'modality' => 'CT'
        ]);
        DB::table('visit_groups')->insert([
            'study_name' => 'Study test',
            'modality' => 'MR'
        ]);

        DB::table('visit_types')->insert([
            'visit_group_id' => 1,
            'name' => 'Test1',
            'visit_order' => 1,
            'limit_low_days' => 1,
            'limit_up_days' => 10,
        ]);
        DB::table('visit_types')->insert([
            'visit_group_id' => 2,
            'name' => 'Test2',
            'visit_order' => 1,
            'limit_low_days' => 1,
            'limit_up_days' => 10,
        ]);
        DB::table('visit_types')->insert([
            'visit_group_id' => 2,
            'name' => 'Test3',
            'visit_order' => 2,
            'limit_low_days' => 1,
            'limit_up_days' => 10,
        ]);
        DB::table('visit_types')->insert([
            'visit_group_id' => 3,
            'name' => 'Test4',
            'visit_order' => 1,
            'limit_low_days' => 1,
            'limit_up_days' => 10,
        ]);

        DB::table('visits')->insert([
            'creator_user_id' => 1,
            'creation_date' => now(),
            'patient_code' => Patient::first()['code'],
            'visit_type_id' => 1
        ]);
        DB::table('visits')->insert([
            'creator_user_id' => 1,
            'creation_date' => now(),
            'patient_code' => Patient::first()['code'],
            'visit_type_id' => 2
        ]);
        DB::table('visits')->insert([
            'creator_user_id' => 1,
            'creation_date' => now(),
            'patient_code' => Patient::first()['code'],
            'visit_type_id' => 3
        ]);
        DB::table('visits')->insert([
            'creator_user_id' => 1,
            'creation_date' => now(),
            'patient_code' => Patient::first()['code'],
            'visit_type_id' => 4
        ]);

        factory(User::class, 50)->create();
    }
}
