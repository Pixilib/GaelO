<?php

namespace Tests\Feature\TestVisits;

use App\GaelO\Constants\Constants;
use Tests\TestCase;
use App\Models\User;
use App\Models\Visit;
use App\Models\Patient;
use App\Models\ReviewStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\AuthorizationTools;

class GetVisitTest extends TestCase
{

    use RefreshDatabase;

    protected function setUp() : void {
        parent::setUp();
        $this->artisan('db:seed');
    }

    private function createVisitInDb(){
        $patient = Patient::factory()->create();
        $visit = Visit::factory()->patientId($patient->id)->create();
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

        $this->json('GET', 'api/visits/'.$visit->id.'?role=Investigator&studyName='.$studyName)->assertStatus(200);
    }

    public function testGetVisitForbiddenNoRole(){

        $visit= $this->createVisitInDb();
        $studyName = $visit->patient->study_name;

        AuthorizationTools::actAsAdmin(false);
        $this->json('GET', 'api/visits/'.$visit->id.'?role=Investigator&studyName='.$studyName)->assertStatus(403);
    }

    public function testGetPatientVisits() {

        $patient = Patient::factory()->create();
        $studyName = $patient->study->name;
        $visit = Visit::factory()->patientId($patient->id)->count(5)->create();

        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_INVESTIGATOR, $studyName);
        $userEntity = User::find($currentUserId);
        $userEntity->center_code = $patient->center_code;
        $userEntity->save();

        $visit->each(function ($visit) use ($studyName)  {
            ReviewStatus::factory()->visitId($visit->id)->studyName($studyName)->create();
        });

        $resp = $this->json('GET', 'api/patients/'.$patient->id.'/visits?role=Investigator&studyName='.$studyName);

        $resp->assertStatus(200);
        $patientArray = json_decode($resp->content(), true);
        $this->assertEquals(5, sizeof($patientArray));


    }

    public function testGetPatientVisitsForbiddenNoRole() {

        $patient = Patient::factory()->create();
        $studyName = $patient->study->name;
        $visit = Visit::factory()->patientId($patient->id)->count(5)->create();

        AuthorizationTools::actAsAdmin(false);

        $visit->each(function ($visit) use ($studyName)  {
            ReviewStatus::factory()->visitId($visit->id)->studyName($studyName)->create();
        });

        $resp = $this->json('GET', 'api/patients/'.$patient->id.'/visits?role=Investigator&studyName='.$studyName);
        $resp->assertStatus(403);
    }

    public function testGetVisitsFromStudy() {
        $visit= $this->createVisitInDb();
        $studyName = $visit->patient->study->name;

        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_SUPERVISOR, $studyName);

        $answer = $this->json('GET', 'api/visits/'.$visit->id.'?role=Supervisor&action='.$studyName.'&studyName='.$studyName);
        $answer->assertStatus(200);
    }

    public function testGetVisitsFromStudyShouldFailNoRole() {
        $visit= $this->createVisitInDb();
        $studyName = $visit->patient->study->name;

        AuthorizationTools::actAsAdmin(false);

        $answer = $this->json('GET', 'api/visits/'.$visit->id.'?role=Supervisor&action='.$studyName.'&studyName='.$studyName);
        $answer->assertStatus(403);
    }

}
