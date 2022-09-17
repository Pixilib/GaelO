<?php


namespace Tests\Feature\TestVisits;

use App\GaelO\Constants\Constants;
use App\Models\Visit;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\AuthorizationTools;
use Tests\TestCase;

class ModifyVisitDateTest extends TestCase {

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
        $this->visit = Visit::factory()->create();
        $this->studyName = $this->visit->patient->study_name;
    }

    public function testModifyVisitDate()
    {
        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_SUPERVISOR, $this->studyName);

        $payload = [
            'visitDate' => now()
        ];

        $response = $this->put('/api/visits/'.$this->visit->id.'/visit-date?studyName='.$this->studyName, $payload);

        $response->assertStatus(200);

    }

    public function testModifyVisitDateShouldFailWrongStudy()
    {
        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_SUPERVISOR, $this->studyName);

        $payload = [
            'visitDate' => now()
        ];

        $response = $this->put('/api/visits/'.$this->visit->id.'/visit-date?studyName='.$this->studyName. 'wrong', $payload);

        $response->assertStatus(403);

    }

    public function testModifyVisitDateShouldFailNoRole()
    {
        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_INVESTIGATOR, $this->studyName);

        $payload = [
            'visitDate' => now()
        ];

        $response = $this->put('/api/visits/'.$this->visit->id.'/visit-date?studyName='.$this->studyName, $payload);

        $response->assertStatus(403);

    }


}
