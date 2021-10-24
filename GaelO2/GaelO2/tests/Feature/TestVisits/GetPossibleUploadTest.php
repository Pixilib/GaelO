<?php

namespace Tests\Feature\TestVisits;

use App\GaelO\Constants\Constants;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\AuthorizationTools;
use Tests\TestCase;
class GetPossibleUploadTest extends TestCase
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


    protected function setUp() : void{
        parent::setUp();
        $visit = Visit::factory()->create();
        $this->patient = $visit->patient;
        $this->studyName = $visit->visitType->visitGroup->study_name;
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
