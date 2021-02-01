<?php

namespace Tests\Feature\TestVisits;

use App\GaelO\Constants\Constants;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;
use App\Models\User;
use App\Models\Visit;
use App\Models\Patient;
use App\Models\ReviewStatus;
use Tests\AuthorizationTools;

class GetVisitTest extends TestCase
{

    use DatabaseMigrations {
        runDatabaseMigrations as baseRunDatabaseMigrations;
    }

    /**
     * Define hooks to migrate the database before and after each test.
     *
     * @return void
     */
    public function runDatabaseMigrations()
    {
        $this->baseRunDatabaseMigrations();
        $this->artisan('db:seed');
    }

    protected function setUp() : void {
        parent::setUp();
    }

    private function createVisitInDb(){
        $patient = Patient::factory()->create();
        $visit = Visit::factory()->patientCode($patient->code)->create();
        ReviewStatus::factory()->studyName($patient->study_name)->visitId($visit->id)->create();
        return $visit;
    }

    public function testGetVisit(){

        $visit= $this->createVisitInDb();
        $studyName = $visit->patient->study->name;
        $centerCode = $visit->patient->center->code;

        $currentUserId = AuthorizationTools::actAsAdmin(false);
        //change current user center to match patient center to pass authorization access
        $userEntity = User::find($currentUserId);
        $userEntity->center_code = $centerCode;
        $userEntity->save();

        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_INVESTIGATOR, $studyName);

        $this->json('GET', 'api/studies/'.$studyName.'/visits/'.$visit->id.'?role=Investigator')->assertStatus(200);
    }

    public function testGetVisitForbiddenNoRole(){

        $visit= $this->createVisitInDb();
        $studyName = $visit->visitType->visitGroup->study_name;

        AuthorizationTools::actAsAdmin(false);
        $this->json('GET', 'api/studies/'.$studyName.'/visits/'.$visit->id.'?role=Investigator')->assertStatus(403);
    }

    public function testGetPatientVisits() {

        $patient = Patient::factory()->create();
        $studyName = $patient->study->name;
        $visit = Visit::factory()->patientCode($patient->code)->count(5)->create();

        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_INVESTIGATOR, $studyName);
        $userEntity = User::find($currentUserId);
        $userEntity->center_code = $patient->center_code;
        $userEntity->save();

        $visit->each(function ($visit) use ($studyName)  {
            ReviewStatus::factory()->visitId($visit->id)->studyName($studyName)->create();
        });

        $resp = $this->json('GET', 'api/studies/'.$studyName.'/patients/'.$patient->code.'/visits?role=Investigator');

        $resp->assertStatus(200);
        $patientArray = json_decode($resp->content(), true);
        $this->assertEquals(5, sizeof($patientArray));


    }


    public function testGetPatientVisitsForbiddenNoRole() {

        $patient = Patient::factory()->create();
        $studyName = $patient->study->name;
        $visit = Visit::factory()->patientCode($patient->code)->count(5)->create();

        AuthorizationTools::actAsAdmin(false);

        $visit->each(function ($visit) use ($studyName)  {
            ReviewStatus::factory()->visitId($visit->id)->studyName($studyName)->create();
        });

        $resp = $this->json('GET', 'api/studies/'.$studyName.'/patients/'.$patient->code.'/visits?role=Investigator');
        $resp->assertStatus(403);

    }

}
