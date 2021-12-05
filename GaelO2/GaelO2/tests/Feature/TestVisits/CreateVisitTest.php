<?php

namespace Tests\Feature\TestVisits;

use App\GaelO\Constants\Constants;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;
use App\Models\User;
use App\Models\Visit;
use App\Models\VisitType;
use App\Models\Patient;
use App\Models\Study;
use Tests\AuthorizationTools;

class CreateVisitTest extends TestCase
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

        $visitType=VisitType::factory()->create();
        $this->visitTypeId = $visitType->id;
        $this->visitGroupId = $visitType->visitGroup->id;
        $this->studyName = $visitType->visitGroup->study_name;

        $this->patient = Patient::factory()->studyName($this->studyName)->create();
        $centerCode = $this->patient->center_code;

        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_INVESTIGATOR, $this->studyName);

        $userEntity = User::find($currentUserId);
        $userEntity->center_code = $centerCode;
        $userEntity->save();


    }

    public function testCreateVisit() {

        $validPayload = [
            'patientId' => $this->patient->id,
            'visitDate' => '2020-01-01',
            'statusDone' => 'Done',
        ];

        $this->json('POST', 'api/visit-types/'.$this->visitTypeId.'/visits?role=Investigator', $validPayload)->assertStatus(201);
    }

    public function testCreateVisitNotDone() {

        $validPayload = [
            'patientId' => $this->patient->id,
            'statusDone' => Constants::VISIT_STATUS_NOT_DONE,
            'reasonForNotDone'=> 'unavailable'
        ];

        $this->json('POST', 'api/visit-types/'.$this->visitTypeId.'/visits?role=Investigator', $validPayload)->assertStatus(201);
    }

    public function testCreateVisitForbiddenNoRole(){

        AuthorizationTools::actAsAdmin(false);

        $validPayload = [
            'patientId' => $this->patient->id,
            'visitDate' => '2020-01-01',
            'statusDone' => 'Done',
        ];

        $this->json('POST', 'api/visit-types/'.$this->visitTypeId.'/visits?role=Investigator', $validPayload)->assertStatus(403);
    }

    public function testCreateVisitWrongDate(){

        $validPayload = [
            'patientId' => $this->patient->id,
            'visitDate' => '2020-13-12',
            'statusDone' => 'Done',
        ];

        $this->json('POST', 'api/visit-types/'.$this->visitTypeId.'/visits?role=Investigator', $validPayload)->assertStatus(400);
    }

    public function testCreateVisitNotDoneWithNoReasonShouldFail(){

        $validPayload = [
            'patientId' => $this->patient->id,
            'visitDate' => '2020-01-01',
            'statusDone' => Constants::VISIT_STATUS_NOT_DONE,
        ];

        $this->json('POST', 'api/visit-types/'.$this->visitTypeId.'/visits?role=Investigator', $validPayload)->assertStatus(400);
    }


    public function testCreateAlreadyCreatedVisit(){

        $patient = Patient::factory()->create();
        $visit=Visit::factory()->patientId($patient->id)->create();

        $studyName = $patient->study->name;
        $centerCode = $patient->center_code;

        $currentUserId = AuthorizationTools::actAsAdmin(false);
        $userEntity = User::find($currentUserId);
        $userEntity->center_code = $centerCode;
        $userEntity->save();

        $validPayload = [
            'patientId' => $patient->id,
            'visitDate' => '2020-01-01',
            'statusDone' => Constants::VISIT_STATUS_DONE,
        ];

        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_INVESTIGATOR, $studyName);

        //create request should return conflict
        $this->json('POST', 'api/visit-types/'.$visit->visitType->id.'/visits'.'?role=Investigator', $validPayload)
        ->assertStatus(409);

    }

}
