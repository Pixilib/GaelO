<?php

namespace Tests\Feature;

use App\GaelO\Constants\Constants;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Artisan;
use Laravel\Passport\Passport;
use Tests\TestCase;
use App\User;
use App\Study;
use App\Visit;
use App\VisitGroup;
use App\VisitType;
use App\Patient;
use App\ReviewStatus;
use App\OrthancSeries;
use App\OrthancStudy;
use Tests\AuthorizationTools;

class DicomTest extends TestCase
{

    use DatabaseMigrations {
        runDatabaseMigrations as baseRunDatabaseMigrations;
    }

    public function runDatabaseMigrations() {
        $this->baseRunDatabaseMigrations();
        $this->artisan('db:seed');

        if (true) {
            $this->markTestSkipped('Needs Orthanc To Be Tested');
        }

    }

    protected function setUp() : void{
        parent::setUp();

        Artisan::call('passport:install');
        Passport::actingAs(
            User::where('id',1)->first()
        );

        $this->study = factory(Study::class)->create(['name' => 'test', 'patient_code_prefix' => 1234]);
        $this->visitGroup = factory(VisitGroup::class)->create(['study_name' => 'test']);
        $this->visitType = factory(VisitType::class)->create(['visit_group_id' => $this->visitGroup['id']]);
        $this->patient = factory(Patient::class)->create(['code' => 12341234123412, 'study_name' => 'test', 'center_code' => 0]);
        $this->visit = factory(Visit::class)->create(['creator_user_id' => 1,
        'patient_code' => $this->patient['code'],
        'visit_type_id' => $this->visitType['id'],
        'status_done' => 'Done',
        'upload_status'=> 'Done']);

        $this->reviewStatus = factory(ReviewStatus::class)->create([
            'visit_id' => $this->visit->id,
            'study_name'=> $this->study->name,
        ]);

        $this->orthancStudy = factory(OrthancStudy::class)->create([
            'visit_id' => $this->visit->id,
            'uploader_id'=>1
        ]);

        $this->orthancSeries = factory(OrthancSeries::class)->create([
            'orthanc_study_id' => $this->orthancStudy->orthanc_id,
            'orthanc_id'=>'3fdb6134-48108989-d5b3b9e3-24a6d11c-0a0f360b'
        ]);

    }

    public function testGetOrthancZip(){
        AuthorizationTools::addRoleToUser(1, Constants::ROLE_INVESTIGATOR, $this->study->name);
        $answer = $this->get('api/visits/1/dicoms?role=Investigator');
        $answer->assertStatus(200);

    }

    public function testGetOrthancShouldFailBeacauseNoRole(){
        $answer = $this->get('api/visits/1/dicoms?role=Investigator');
        $answer->assertStatus(403);
    }
}
