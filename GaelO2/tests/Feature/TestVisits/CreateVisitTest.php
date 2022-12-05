<?php

namespace Tests\Feature\TestVisits;

use App\GaelO\Constants\Constants;
use Tests\TestCase;
use App\Models\User;
use App\Models\Visit;
use App\Models\VisitType;
use App\Models\Patient;
use App\Models\Study;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\AuthorizationTools;

class CreateVisitTest extends TestCase
{

    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed');
        $visitType = VisitType::factory()->create();
        $this->study = $visitType->visitGroup->study;
        $this->visitTypeId = $visitType->id;
        $this->visitGroupId = $visitType->visitGroup->id;
        $this->studyName = $visitType->visitGroup->study_name;

        $this->patient = Patient::factory()->inclusionStatus(Constants::PATIENT_INCLUSION_STATUS_INCLUDED)->studyName($this->studyName)->create();
        $centerCode = $this->patient->center_code;

        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_INVESTIGATOR, $this->studyName);

        $userEntity = User::find($currentUserId);
        $userEntity->center_code = $centerCode;
        $userEntity->save();
    }

    public function testCreateVisit()
    {

        $validPayload = [
            'patientId' => $this->patient->id,
            'visitDate' => '2020-01-01',
            'statusDone' => 'Done',
        ];

        $this->json('POST', 'api/visit-types/' . $this->visitTypeId . '/visits?role=Investigator&studyName=' . $this->studyName, $validPayload)->assertStatus(201);
    }

    public function testCreateVisitShouldBeForbiddenForAncillaries()
    {
        $ancillaryStudy = Study::factory()->ancillaryOf($this->studyName)->create();

        //Use supervisor role (investigator will be forbiden anyway for an ancillary study)
        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_SUPERVISOR, $ancillaryStudy->name);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_INVESTIGATOR, $ancillaryStudy->name);

        $validPayload = [
            'patientId' => $this->patient->id,
            'visitDate' => '2020-01-01',
            'statusDone' => 'Done',
        ];

        $answer = $this->json('POST', 'api/visit-types/' . $this->visitTypeId . '/visits?role=Supervisor&studyName=' . $ancillaryStudy->name, $validPayload);
        $answer->assertStatus(403);
        $answer = $this->json('POST', 'api/visit-types/' . $this->visitTypeId . '/visits?role=Investigator&studyName=' . $ancillaryStudy->name, $validPayload);
        $answer->assertStatus(403);
    }

    public function testCreateUnavailableVisitTypeIdShouldBeForbidden()
    {

        $validPayload = [
            'patientId' => $this->patient->id,
            'visitDate' => '2020-01-01',
            'statusDone' => 'Done',
        ];

        $this->json('POST', 'api/visit-types/50/visits?role=Investigator&studyName=' . $this->studyName, $validPayload)->assertStatus(403);
    }

    public function testCreateVisitNotDone()
    {

        $validPayload = [
            'patientId' => $this->patient->id,
            'statusDone' => Constants::VISIT_STATUS_NOT_DONE,
            'reasonForNotDone' => 'unavailable'
        ];

        $this->json('POST', 'api/visit-types/' . $this->visitTypeId . '/visits?role=Investigator&studyName=' . $this->studyName, $validPayload)->assertStatus(201);
    }

    public function testCreateVisitForbiddenNoRole()
    {

        AuthorizationTools::actAsAdmin(false);

        $validPayload = [
            'patientId' => $this->patient->id,
            'visitDate' => '2020-01-01',
            'statusDone' => 'Done',
        ];

        $this->json('POST', 'api/visit-types/' . $this->visitTypeId . '/visits?role=Investigator&studyName=' . $this->studyName, $validPayload)->assertStatus(403);
    }

    public function testCreateVisitAsSupervisor()
    {

        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_SUPERVISOR, $this->studyName);

        $validPayload = [
            'patientId' => $this->patient->id,
            'visitDate' => '2020-01-01',
            'statusDone' => 'Done',
        ];

        $this->json('POST', 'api/visit-types/' . $this->visitTypeId . '/visits?role=Supervisor&studyName=' . $this->studyName, $validPayload)->assertStatus(201);
    }

    public function testCreateVisitForbiddenRole()
    {

        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_CONTROLLER, $this->studyName);

        $validPayload = [
            'patientId' => $this->patient->id,
            'visitDate' => '2020-01-01',
            'statusDone' => 'Done',
        ];

        $this->json('POST', 'api/visit-types/' . $this->visitTypeId . '/visits?role=Controller&studyName=' . $this->studyName, $validPayload)->assertStatus(403);
    }

    public function testCreateVisitWrongDate()
    {

        $validPayload = [
            'patientId' => $this->patient->id,
            'visitDate' => '2020-13-12',
            'statusDone' => 'Done',
        ];

        $this->json('POST', 'api/visit-types/' . $this->visitTypeId . '/visits?role=Investigator&studyName=' . $this->studyName, $validPayload)->assertStatus(400);
    }

    public function testCreateVisitNotDoneWithNoReasonShouldFail()
    {

        $validPayload = [
            'patientId' => $this->patient->id,
            'visitDate' => '2020-01-01',
            'statusDone' => Constants::VISIT_STATUS_NOT_DONE,
        ];

        $this->json('POST', 'api/visit-types/' . $this->visitTypeId . '/visits?role=Investigator&studyName=' . $this->studyName, $validPayload)->assertStatus(400);
    }

    public function testCreateVisitNotIncludedPatientShouldFail()
    {
        $validPayload = [
            'patientId' => $this->patient->id,
            'visitDate' => '2020-01-01',
            'statusDone' => 'Done',
        ];

        $this->patient->inclusion_status = Constants::PATIENT_INCLUSION_STATUS_EXCLUDED;
        $this->patient->save();
        $this->json('POST', 'api/visit-types/' . $this->visitTypeId . '/visits?role=Investigator&studyName=' . $this->studyName, $validPayload)->assertStatus(403);
    }


    public function testCreateAlreadyCreatedVisit()
    {

        $patient = Patient::factory()->inclusionStatus(Constants::PATIENT_INCLUSION_STATUS_INCLUDED)->create();
        $visit = Visit::factory()->patientId($patient->id)->create();

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
        $this->json('POST', 'api/visit-types/' . $visit->visitType->id . '/visits' . '?role=Investigator&studyName=' . $studyName, $validPayload)
            ->assertStatus(409);
    }
}
