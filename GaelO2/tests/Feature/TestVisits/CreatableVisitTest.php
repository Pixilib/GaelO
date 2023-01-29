<?php

namespace Tests\Feature\TestVisits;

use App\GaelO\Constants\Constants;
use App\GaelO\Constants\Enums\InclusionStatusEnum;
use App\Models\Patient;
use App\Models\User;
use App\Models\VisitGroup;
use App\Models\VisitType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\AuthorizationTools;
use Tests\TestCase;

class CreatableVisitTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed');
        $this->patient = Patient::factory()->inclusionStatus(InclusionStatusEnum::INCLUDED->value)->create();
        $this->studyName = $this->patient->study_name;
        $this->patientId = $this->patient->id;
        //Create a Visit Type to have one creatable visit
        $visitGroup = VisitGroup::factory()->studyName($this->studyName)->create();
        VisitType::factory()->visitGroupId($visitGroup->id)->create();
    }

    public function testGetCreatableVisit()
    {
        $currentUserId = AuthorizationTools::actAsAdmin(false);

        $userEntity = User::find($currentUserId);
        $userEntity->center_code = $this->patient->center_code;
        $userEntity->save();

        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_INVESTIGATOR, $this->studyName);
        $response = $this->get('/api/patients/' . $this->patientId . '/creatable-visits');
        $responseArray = json_decode( $response->content(), true );
        $this->assertEquals(1, sizeof($responseArray));
        $this->assertArrayHasKey('order', $responseArray[0]);
        $this->assertArrayHasKey('name', $responseArray[0]);
        $this->assertArrayHasKey('optional', $responseArray[0]);
        $response->assertStatus(200);
    }

    public function testGetCreatableVisitShouldFailNoRole()
    {
        AuthorizationTools::actAsAdmin(false);
        $response = $this->get('/api/patients/' . $this->patientId . '/creatable-visits');
        $response->assertStatus(403);
    }
}
