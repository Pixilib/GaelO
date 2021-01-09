<?php

namespace Database\Seeders;

use App\Model\Center;
use App\Model\Patient;
use App\Model\ReviewStatus;
use App\Model\Study;
use App\Model\User;
use App\Model\Visit;
use App\Model\VisitGroup;
use App\Model\VisitType;
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
            CenterSeeder::class
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

        $this->study = factory(Study::class)->create([
            'name' => 'StudyTest',
            'patient_code_prefix' => '123'
        ]);

        DB::table('roles')->insert([
            'name' => 'Supervisor',
            'user_id' => '1',
            'study_name' => 'StudyTest',
        ]);
        DB::table('roles')->insert([
            'name' => 'Monitor',
            'user_id' => '1',
            'study_name' => 'StudyTest',
        ]);
        DB::table('roles')->insert([
            'name' => 'Investigator',
            'user_id' => '1',
            'study_name' => 'StudyTest',
        ]);
        DB::table('roles')->insert([
            'name' => 'Controller',
            'user_id' => '1',
            'study_name' => 'StudyTest',
        ]);


        factory(Patient::class, 1)->create(['code' => 123000 + rand(0,999), 'inclusion_status' => 'Included', 'investigator_name' => 'administrator', 'study_name' => 'StudyTest', 'center_code' => 0]);
        factory(Patient::class, 4)->create(['investigator_name' => 'administrator', 'study_name' => 'StudyTest', 'center_code' => 0]);
        $this->visitGroup = factory(VisitGroup::class)->create(['study_name' => 'StudyTest']);
        $this->visitType = factory(VisitType::class)->create(['visit_group_id' => $this->visitGroup['id']]);

        $visit = factory(Visit::class)->create(['creator_user_id' => 1,
        'patient_code' => Patient::first()['code'],
        'visit_type_id' => $this->visitType['id'],
        'status_done' => 'Done']);

        factory(ReviewStatus::class)->create([
        'study_name' => $this->study->name,
        'visit_id' => $visit->id
        ]);
        /*DB::table('visit_groups')->insert([
            'study_name' => 'StudyTest',
            'modality' => 'PT'
        ]);
        DB::table('visit_groups')->insert([
            'study_name' => 'StudyTest',
            'modality' => 'CT'
        ]);
        DB::table('visit_groups')->insert([
            'study_name' => 'StudyTest',
            'modality' => 'MR'
        ]);

        DB::table('visit_types')->insert([
            'visit_group_id' => 1,
            'name' => 'Test1',
            'order' => 1,
            'limit_low_days' => 1,
            'limit_up_days' => 10,
        ]);
        DB::table('visit_types')->insert([
            'visit_group_id' => 2,
            'name' => 'Test2',
            'order' => 1,
            'limit_low_days' => 1,
            'limit_up_days' => 10,
        ]);
        DB::table('visit_types')->insert([
            'visit_group_id' => 2,
            'name' => 'Test3',
            'order' => 2,
            'limit_low_days' => 1,
            'limit_up_days' => 10,
        ]);
        DB::table('visit_types')->insert([
            'visit_group_id' => 3,
            'name' => 'Test4',
            'order' => 1,
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
            'status_done' => 'Done',
            'upload_status' => 'Not Done',
            'visit_type_id' => 3
        ]);
        DB::table('visits')->insert([
            'creator_user_id' => 1,
            'creation_date' => now(),
            'patient_code' => Patient::first()['code'],
            'visit_type_id' => 4,
            'status_done' => 'Done',
            'upload_status' => 'Done',
            'state_investigator_form' => 'Draft',
            'state_quality_control' => 'Wait Definitive Conclusion'
        ]);*/

        factory(User::class, 50)->create();
    }
}
