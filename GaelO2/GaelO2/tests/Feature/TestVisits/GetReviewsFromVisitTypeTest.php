<?php

namespace Tests\Feature\TestVisits;

use App\GaelO\Constants\Constants;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;
use App\Models\Visit;
use App\Models\Review;
use App\Models\ReviewStatus;
use App\Models\Study;
use App\Models\VisitGroup;
use App\Models\VisitType;
use Tests\AuthorizationTools;

class GetReviewsFromVisitTypeTest extends TestCase
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

    protected function setUp(): void
    {
        parent::setUp();

        $study = Study::factory()->create();
        $visitGroup = VisitGroup::factory()->studyName($study->name)->create();
        $visitType = VisitType::factory()->visitGroupId($visitGroup->id)->count(2)->create();

        $visitType->each(function ($visitType, $key) use ($study) {
            $visit = Visit::factory()->visitTypeId($visitType->id)->create();
            Review::factory()->visitId($visit->id)->reviewForm()->studyName($study->name)->create();
            Review::factory()->visitId($visit->id)->studyName($study->name)->create();
            ReviewStatus::factory()->studyName($study->name)->visitId($visit->id)->create();
        });

        $this->studyName = $study->name;
        $this->visitTypeId = $visitType->first()->id;
    }


    public function testGetReviewsFromVisitType()
    {

        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_SUPERVISOR, $this->studyName);
        $answer = $this->json('GET', 'api/visit-types/' . $this->visitTypeId . '/reviews?studyName='.$this->studyName);
        $answer->assertStatus(200);
    }

    public function testGetReviewsFromVisitTypeShouldFailNotSupervisor()
    {

        AuthorizationTools::actAsAdmin(false);
        $answer = $this->json('GET', 'api/visit-types/' . $this->visitTypeId . '/visits?studyName='.$this->studyName);
        $answer->assertStatus(403);
    }


    public function testGetInvestigatorFormsFromVisitType()
    {

        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_SUPERVISOR, $this->studyName);
        $answer = $this->json('GET', 'api/visit-types/' . $this->visitTypeId . '/investigator-forms?studyName='.$this->studyName);
        $answer->assertStatus(200);
    }

    public function testGetInvestigatorFormsFromVisitTypeShouldFailNotSupervisor()
    {

        AuthorizationTools::actAsAdmin(false);
        $answer = $this->json('GET', 'api/visit-types/' . $this->visitTypeId . '/investigator-forms?studyName='.$this->studyName);
        $answer->assertStatus(403);
    }


}
