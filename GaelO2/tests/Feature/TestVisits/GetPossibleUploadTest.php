<?php

namespace Tests\Feature\TestVisits;

use App\GaelO\Constants\Constants;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\AuthorizationTools;
use Tests\TestCase;
class GetPossibleUploadTest extends TestCase
{

    use RefreshDatabase;

    protected function setUp() : void{
        parent::setUp();
        $this->artisan('db:seed');
        $visit = Visit::factory()->create();
        $this->patient = $visit->patient;
        $this->studyName = $visit->patient->study_name;
    }

    public function testGetPossibleUpload()
    {
        $currentUserId = AuthorizationTools::actAsAdmin(false);

        $userModel = User::find($currentUserId);
        $userModel->center_code = $this->patient->center_code;
        $userModel->save();

        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_INVESTIGATOR, $this->studyName);

        $response = $this->get('api/studies/'.$this->studyName.'/possible-uploads');
        $response->assertStatus(200);
    }

    public function testGetPossibleUploadFailNoRole()
    {
        AuthorizationTools::actAsAdmin(false);
        $response = $this->get('api/studies/'.$this->studyName.'/possible-uploads');
        $response->assertStatus(403);
    }
}
