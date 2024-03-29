<?php

namespace Tests\Feature\TestPatients;

use App\GaelO\Constants\Constants;
use App\GaelO\Constants\Enums\InclusionStatusEnum;
use App\Models\Patient;
use App\Models\Study;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\AuthorizationTools;

class ModifyPatientTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed');
        //Fill patient table
        $this->study = Study::factory()->create();
        $this->studyName = $this->study->name;
        $this->patient = Patient::factory()->studyName($this->studyName)->create();

        $this->validPayload = [
            "id" => $this->patient['id'],
            "code" => $this->patient['code'],
            "firstname" => "a",
            "lastname" => "b",
            "gender" => "M",
            "birthDay" => 23,
            "birthMonth" => 1,
            "birthYear" => 1985,
            "registrationDate" => "2011-10-05",
            "investigatorName" => "voluptas",
            "centerCode" => $this->patient['center_code'],
            "studyName" => $this->patient['study_name'],
            "inclusionStatus" => "Included",
            "birthDay" => 5,
            "birthMonth" => 12,
            "birthYear" => 1955,
            "metadata" => ['tags'=>['Salim']]
        ];
    }

    public function testModifyPatient()
    {
        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_SUPERVISOR, $this->study->name);

        $payload = $this->validPayload;
        $payload = array_merge($payload, [
            'firstname' => 'a',
            'lastname' => 'b',
            'gender' => 'M',
            'birthDay' => 5,
            'birthMonth' => 12,
            'birthYear' => 1955,
            'registrationDate' => '2011-10-05',
            'investigatorName' => 'salim',
            'centerCode' => 0,
            'reason' => 'wrong patient data'
        ]);

        $this->json('PATCH', '/api/patients/' . $this->patient->id . '?studyName=' . $this->studyName, $payload)->assertStatus(200);
    }

    public function testModifyPatientShouldFailWrongStudy()
    {
        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_SUPERVISOR, $this->study->name);

        $payload = $this->validPayload;
        $payload = array_merge($payload, [
            'firstname' => 'a',
            'lastname' => 'b',
            'gender' => 'M',
            'birthDay' => 5,
            'birthMonth' => 12,
            'birthYear' => 1955,
            'registrationDate' => '2011-10-05',
            'investigatorName' => 'salim',
            'centerCode' => 0,
            'reason' => 'wrong patient data'
        ]);

        $this->json('PATCH', '/api/patients/' . $this->patient->id . '?studyName=' . $this->studyName.'wrong', $payload)->assertStatus(403);
    }

    public function testModifyPatientWrongData()
    {
        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_SUPERVISOR, $this->study->name);

        $this->json('PATCH', '/api/patients/' . $this->patient->id . '?studyName=' . $this->studyName, ['gender' => 'G'])->assertStatus(400);
        $this->json('PATCH', '/api/patients/' . $this->patient->id . '?studyName=' . $this->studyName, ['birthDay' => 32])->assertStatus(400);
        $this->json('PATCH', '/api/patients/' . $this->patient->id . '?studyName=' . $this->studyName, ['birthMonth' => 13])->assertStatus(400);
        $this->json('PATCH', '/api/patients/' . $this->patient->id . '?studyName=' . $this->studyName, ['birthYear' => 5000])->assertStatus(400);
        $this->json('PATCH', '/api/patients/' . $this->patient->id . '?studyName=' . $this->studyName, ['registrationDate' => '2011-10-05'])->assertStatus(400);
    }

    public function testModifyPatientForbidenNotSupervisor()
    {
        AuthorizationTools::actAsAdmin(false);
        $this->json('PATCH', '/api/patients/' . $this->patient->id . '?studyName=' . $this->studyName, ['reason' => 'wrong patient data'])->assertStatus(403);
    }

    public function testModifyPatientBadRequestMissingReason()
    {
        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_SUPERVISOR, $this->study->name);
        $this->json('PATCH', '/api/patients/' . $this->patient->id . '?studyName=' . $this->studyName, ['gender' => 'M'])->assertStatus(400);
    }

    public function testModifyPatientInclusionStatus()
    {
        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_SUPERVISOR, $this->study->name);

        $payload = $this->validPayload;
        $payload = array_merge($payload, [
            'inclusionStatus' => InclusionStatusEnum::WITHDRAWN->value,
            'withdrawDate' => '2011-10-05',
            'withdrawReason' => 'fed-up',
            'reason' => 'inclusion status changed'
        ]);
        $this->json('PATCH', '/api/patients/' . $this->patient->id . '?studyName=' . $this->studyName, $payload)->assertStatus(200);
        $updatedPatientEntity = Patient::find($this->patient->id)->toArray();
        $this->assertEquals(InclusionStatusEnum::WITHDRAWN->value, $updatedPatientEntity['inclusion_status']);
        $this->assertEquals('2011-10-05T00:00:00.000000Z', $updatedPatientEntity['withdraw_date']);
        $this->assertEquals($payload['withdrawReason'], $updatedPatientEntity['withdraw_reason']);
    }

    public function testModifyPatientMetadataShouldFailMissingTagsKey()
    {
        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_SUPERVISOR, $this->study->name);

        $payload = $this->validPayload;
        $payload = array_merge($payload, [
            'metadata' => [],
        ]);
        $this->json('PATCH', '/api/patients/' . $this->patient->id . '?studyName=' . $this->studyName, $payload)->assertStatus(400);

    }

    public function testModifyPatientMetadataShouldFailTagsKeyNotArray()
    {
        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_SUPERVISOR, $this->study->name);

        $payload = $this->validPayload;
        $payload = array_merge($payload, [
            'metadata' => ['tags' => 'salim'],
        ]);
        $this->json('PATCH', '/api/patients/' . $this->patient->id . '?studyName=' . $this->studyName, $payload)->assertStatus(400);

    }


    public function testModifyPatientWithdrawForbiddenNoRole()
    {
        AuthorizationTools::actAsAdmin(false);

        $payload = $this->validPayload;
        $payload = array_merge($payload, [
            'inclusionStatus' => InclusionStatusEnum::INCLUDED->value,
            'withdrawDate' => '2011-10-05',
            'withdrawReason' => 'fed-up',
            'reason' => 'inclusion status changed'
        ]);

        $this->json('PATCH', '/api/patients/' . $this->patient->id . '?studyName=' . $this->studyName, $payload)->assertStatus(403);
    }

    public function testModifyPatientRemoveWithdraw()
    {
        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_SUPERVISOR, $this->study->name);
        $this->patient->inclusion_status = InclusionStatusEnum::WITHDRAWN->value;
        $this->patient->withdraw_reason = 'personal';
        $this->patient->withdraw_date = now();
        $this->patient->save();

        $payload = $this->validPayload;
        $payload['inclusionStatus'] = InclusionStatusEnum::INCLUDED->value;
        $payload['reason'] = 'inclusion status changed';

        $this->json('PATCH', '/api/patients/' . $this->patient->id . '?studyName=' . $this->studyName, $payload)->assertStatus(200);
        $updatedPatientEntity = Patient::find($this->patient->id)->toArray();
        $this->assertEquals(InclusionStatusEnum::INCLUDED->value, $updatedPatientEntity['inclusion_status']);
        $this->assertNull($updatedPatientEntity['withdraw_date']);
        $this->assertNull($updatedPatientEntity['withdraw_reason']);
    }
}
