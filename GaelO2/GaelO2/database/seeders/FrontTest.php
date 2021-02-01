<?php

namespace Database\Seeders;

use App\Models\Center;
use App\Models\Patient;
use App\Models\ReviewStatus;
use App\Models\Study;
use App\Models\User;
use App\Models\Visit;
use App\Models\VisitGroup;
use App\Models\VisitType;
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

        Study::factory()->count(5)->create();

        $this->study = Study::factory()->name('StudyTest')->patientCodePrefix('123')->create();

        DB::table('roles')->insert([
            'name' => 'Supervisor',
            'user_id' => '1',
            'study_name' => $this->study->name,
        ]);
        DB::table('roles')->insert([
            'name' => 'Monitor',
            'user_id' => '1',
            'study_name' => $this->study->name,
        ]);
        DB::table('roles')->insert([
            'name' => 'Investigator',
            'user_id' => '1',
            'study_name' => $this->study->name,
        ]);
        DB::table('roles')->insert([
            'name' => 'Controller',
            'user_id' => '1',
            'study_name' => $this->study->name,
        ]);


        Patient::factory()->code(123000 + rand(0,999))->inclusionStatus('Included')
            ->investigatorName('administrator')->studyName($this->study->name)->centerCode(0)->create();
        
        Patient::factory()->count(4)->investigatorName('administrator')
            ->studyName($this->study->name)->centerCode(0)->create();

        $this->visitGroup = VisitGroup::factory()->studyName($this->study->name)->create();
        VisitType::factory()->count(6)->visitGroupId($this->visitGroup['id'])->create();

        $this->visitGroup = VisitGroup::factory()->studyName($this->study->name)->modality('PT')->create();
        $this->visitType = VisitType::factory()->visitGroupId($this->visitGroup['id'])->create();
        $visit = Visit::factory()->creatorUserId(1)->patientCode(Patient::first()['code'])
            ->visitTypeId($this->visitType['id'])->done()->create();

        ReviewStatus::factory()->studyName($this->study->name)->visitId($visit->id)->create();

        User::factory()->count(50)->create();
    }
}
