<?php

namespace Tests\Feature\TestPatients;

use App\GaelO\Constants\Constants;
use App\Models\Patient;
use App\Models\Study;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\AuthorizationTools;

class PatientAncillaryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed');
        //Fill patient tableTest
        $this->study = Study::factory()->create();
        $this->ancillaryStudy = Study::factory()->ancillaryOf($this->study->name)->create();
        $this->patient = Patient::factory()->studyName($this->study->name)->create();
    }

    public function testGetPatientReviewerShouldNotContainPatientCenter()
    {

        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_REVIEWER, $this->ancillaryStudy->name);

        //Test get patient 4
        $response = $this->json('GET', '/api/patients/' . $this->patient->id . '?role=Reviewer&studyName='.$this->ancillaryStudy->name);
        $response->assertSuccessful();

        $answer = $response->content();
        $answer = json_decode($answer, true);

        //centerCode should be hidden and center details not in payload
        $this->assertNull($answer['centerCode']);
        $this->assertArrayNotHasKey('centerName', $answer);
        $this->assertArrayNotHasKey('countryCode', $answer);
    }

    public function testGetPatientReviewerShouldFailWrongAncillaryStudy()
    {

        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_REVIEWER, $this->ancillaryStudy->name);

        //Test get patient 4
        $response = $this->json('GET', '/api/patients/' . $this->patient->id . '?role=Reviewer&studyName='.$this->ancillaryStudy->name.'wrong');
        $response->assertStatus(404);
    }

}
