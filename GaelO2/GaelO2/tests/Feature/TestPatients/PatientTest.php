<?php

namespace Tests\Feature\TestPatients;

use App\GaelO\Constants\Constants;
use App\Models\Patient;
use App\Models\Study;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;
use DateTime;
use Tests\AuthorizationTools;

class PatientTest extends TestCase
{
    use DatabaseMigrations {
        runDatabaseMigrations as baseRunDatabaseMigrations;
    }

    public function runDatabaseMigrations()
    {
        $this->baseRunDatabaseMigrations();
        $this->artisan('db:seed');
    }

    protected function setUp(): void
    {
        parent::setUp();

        //Fill patient table
        $this->study = Study::factory()->create();
        $this->patient = Patient::factory()->studyName($this->study->name)->create();
    }

    public function testGetPatient()
    {

        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_SUPERVISOR, $this->study->name);

        //Test get patient 4
        $answer = $this->json('GET', '/api/patients/' . $this->patient->code . '?role=Supervisor');
        $answer->assertStatus(200);

        $expectedKeys = [
            "code",
            "firstname",
            "lastname",
            "gender",
            "birthDay",
            "birthMonth",
            "birthYear",
            "registrationDate",
            "investigatorName",
            "centerCode",
            "centerName",
            "countryCode",
            "studyName",
            "inclusionStatus",
            "withdrawReason",
            "withdrawDate"
        ];

        $answer->assertJsonStructure($expectedKeys);
    }

    public function testGetPatientFailNotSupervisor()
    {
        AuthorizationTools::actAsAdmin(false);
        $this->json('GET', '/api/patients/' . $this->patient->code . '?role=Supervisor')->assertStatus(403);
    }


    public function testGetPatientReviewerShouldNotContainPatientCenter()
    {

        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_REVIEWER, $this->study->name);

        //Test get patient 4
        $response = $this->json('GET', '/api/patients/' . $this->patient->code . '?role=Reviewer');
        $response->assertSuccessful();

        $answer = $response->content();
        $answer = json_decode($answer, true);

        //centerCode should be hidden and center details not in payload
        $this->assertNull($answer['centerCode']);
        $this->assertArrayNotHasKey('centerName', $answer);
        $this->assertArrayNotHasKey('countryCode', $answer);
    }

    public function testGetPatientFromStudy()
    {
        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_SUPERVISOR, $this->study->name);
        $this->json('GET', '/api/studies/' . $this->study->name . '/patients?role=Supervisor')
            ->assertJsonCount(1);
    }

    public function testModifyPatient()
    {
        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_SUPERVISOR, $this->study->name);

        $payload = [
            'firstname' => 'a',
            'lastname' => 'b',
            'gender' => 'M',
            'birthDay' => 5,
            'birthMonth' => 12,
            'birthYear' => 1955,
            'registrationDate' => '12/31/2020',
            'investigatorName' => 'salim',
            'centerCode' => 0,
            'reason' => 'wrong patient data'
        ];

        $this->json('PATCH', '/api/patients/' . $this->patient->code, $payload)->assertStatus(200);
    }

    public function testModifyPatientWrongData()
    {
        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_SUPERVISOR, $this->study->name);

        $this->json('PATCH', '/api/patients/' . $this->patient->code, ['gender' => 'G'])->assertStatus(400);
        $this->json('PATCH', '/api/patients/' . $this->patient->code, ['birthDay' => 32])->assertStatus(400);
        $this->json('PATCH', '/api/patients/' . $this->patient->code, ['birthMonth' => 13])->assertStatus(400);
        $this->json('PATCH', '/api/patients/' . $this->patient->code, ['birthYear' => 5000])->assertStatus(400);
        $this->json('PATCH', '/api/patients/' . $this->patient->code, ['registrationDate' => '31/01/2020'])->assertStatus(400);
    }

    public function testModifyPatientForbidenNotSupervisor()
    {
        AuthorizationTools::actAsAdmin(false);
        $this->json('PATCH', '/api/patients/' . $this->patient->code, ['reason' => 'wrong patient data'])->assertStatus(403);
    }

    public function testModifyPatientBadRequestMissingReason()
    {
        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_SUPERVISOR, $this->study->name);
        $this->json('PATCH', '/api/patients/' . $this->patient->code, ['gender' => 'M'])->assertStatus(400);
    }

    public function testModifyPatientInclusionStatus()
    {
        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_SUPERVISOR, $this->study->name);

        $payload = [
            'inclusionStatus' => Constants::PATIENT_INCLUSION_STATUS_WITHDRAWN,
            'withdrawDate' => '12/31/2020',
            'withdrawReason' => 'fed-up'
        ];

        $this->json('PATCH', '/api/patients/' . $this->patient->code . '/inclusion-status', $payload)->assertStatus(200);
        $updatedPatientEntity = Patient::find($this->patient->code)->toArray();
        $this->assertEquals(Constants::PATIENT_INCLUSION_STATUS_WITHDRAWN, $updatedPatientEntity['inclusion_status']);
        $this->assertEquals(new DateTime($payload['withdrawDate']), new DateTime($updatedPatientEntity['withdraw_date']));
        $this->assertEquals($payload['withdrawReason'], $updatedPatientEntity['withdraw_reason']);
    }

    public function testModifyPatientWithdrawForbiddenNoRole()
    {
        AuthorizationTools::actAsAdmin(false);

        $payload = [
            'inclusionStatus' => Constants::PATIENT_INCLUSION_STATUS_INCLUDED,
            'withdrawDate' => '12/31/2020',
            'withdrawReason' => 'fed-up'
        ];

        $this->json('PATCH', '/api/patients/' . $this->patient->code . '/inclusion-status', $payload)->assertStatus(403);
    }

    public function testModifyPatientRemoveWithdraw()
    {
        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_SUPERVISOR, $this->study->name);

        $payload = [
            'inclusionStatus' => Constants::PATIENT_INCLUSION_STATUS_INCLUDED
        ];

        $this->json('PATCH', '/api/patients/' . $this->patient->code . '/inclusion-status', $payload)->assertStatus(200);
        $updatedPatientEntity = Patient::find($this->patient->code)->toArray();
        $this->assertEquals(Constants::PATIENT_INCLUSION_STATUS_INCLUDED, $updatedPatientEntity['inclusion_status']);
        $this->assertNull($updatedPatientEntity['withdraw_date']);
        $this->assertNull($updatedPatientEntity['withdraw_reason']);
    }
}
