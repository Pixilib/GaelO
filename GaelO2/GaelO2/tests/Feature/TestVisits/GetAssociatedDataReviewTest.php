<?php

namespace Tests\Feature\TestVisits;

use App\GaelO\Constants\Constants;
use App\Models\Patient;
use App\Models\ReviewStatus;
use App\Models\Study;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;
use App\Models\Visit;
use App\Models\VisitGroup;
use App\Models\VisitType;
use Tests\AuthorizationTools;

class GetAssociatedDataReviewTest extends TestCase
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
        $study = Study::factory()->name('TEST')->create();
        $patient = Patient::factory()->studyName($study->name)->create();
        $visitGroup = VisitGroup::factory()->studyName($study->name)->name('PT')->create();
        $visitType  = VisitType::factory()->visitGroupId($visitGroup->id)->name('PET0')->localFormNeeded()->create();
        $visit = Visit::factory()->patientId($patient->id)->visitTypeId($visitType->id)->create();
        ReviewStatus::factory()->studyName($study->name)->visitId($visit->id)->reviewAvailable()->create();

        $this->visit = $visit;
        $this->studyName = $study->name;
    }


    public function testGetAssociatedData(){
        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_REVIEWER, $this->studyName );
        $resp = $this->json('GET', '/api/studies/'.$this->studyName.'/visits/'.$this->visit->id.'/reviewer-associated-data');
        $answer = json_decode($resp->content(), true);
        $this->assertArrayHasKey('Radiotherapy', $answer);
        $resp->assertStatus(200);
    }

    public function testGetAssociatedDataShouldFailNoReviewer(){
        AuthorizationTools::actAsAdmin(false);
        $resp = $this->json('GET', '/api/studies/'.$this->studyName.'/visits/'.$this->visit->id.'/reviewer-associated-data');
        $resp->assertStatus(403);

    }

}
