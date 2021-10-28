<?php

namespace Tests\Feature\TestReviewForm;

use App\GaelO\Constants\Constants;
use App\Models\Patient;
use App\Models\Review;
use App\Models\ReviewStatus;
use App\Models\Study;
use App\Models\Visit;
use App\Models\VisitGroup;
use App\Models\VisitType;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\AuthorizationTools;
use Tests\TestCase;

class GetReviewFormTest extends TestCase
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

        $study = Study::factory()->name('TEST')->create();
        $patient = Patient::factory()->studyName($study->name)->create();
        $visitGroup = VisitGroup::factory()->studyName($study->name)->modality('PT')->create();
        $visitType  = VisitType::factory()->visitGroupId($visitGroup->id)->name('PET0')->localFormNeeded()->create();
        $visit = Visit::factory()->patientCode($patient->code)->visitTypeId($visitType->id)->create();
        ReviewStatus::factory()->studyName($study->name)->visitId($visit->id)->reviewAvailable()->create();
        $this->review = Review::factory()->studyName($study->name)->visitId($visit->id)->reviewForm()->create();
        $this->studyName = $study->name;
    }

    public function testgetReviewForm()
    {
        $currentUserId = AuthorizationTools::actAsAdmin(false);
        $this->review->user_id = $currentUserId;
        $this->review->save();
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_REVIEWER, $this->studyName);
        $this->get('api/reviews/' . $this->review->id)->assertStatus(200);
    }

    public function testgetReviewFormShouldFailNoReviewerRole()
    {
        $currentUserId = AuthorizationTools::actAsAdmin(false);
        $this->review->user_id = $currentUserId;
        $this->review->save();
        $this->get('api/reviews/' . $this->review->id)->assertStatus(403);
    }

    public function testgetReviewFormShouldFailNoOwnForm()
    {
        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_REVIEWER, $this->studyName);
        $this->get('api/reviews/' . $this->review->id)->assertStatus(403);
    }

    public function testGetReviewFromVisit(){

        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_SUPERVISOR, $this->studyName);
        $visitId = $this->review->visit->id;
        $request = $this->get('api/studies/'.$this->studyName.'/visits/'.$visitId.'/reviews');
        $request->assertStatus(200);
        $response = json_decode($request->content());
        $this->assertEquals(1, sizeof($response));

    }

    public function testGetReviewFromVisitShouldFailNoReviewer(){

        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_INVESTIGATOR, $this->studyName);
        $visitId = $this->review->visit->id;
        $request = $this->get('api/studies/'.$this->studyName.'/visits/'.$visitId.'/reviews');
        $request->assertStatus(403);

    }


    public function testGetReviewFormMetadata(){
        $visitTypeId = $this->review->visit->visitType->id;
        $studyName = $this->review->visit->visitType->visitGroup->study->name;

        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_SUPERVISOR, $studyName);

        $answer = $this->get('api/studies/'.$studyName.'/visit-types/'.$visitTypeId.'/reviews/metadata');
        $answer->assertStatus(200);

    }

    public function testGetReviewFormMetadataShouldFailNotSupervisor(){

        $visitTypeId = $this->review->visit->visitType->id;
        $studyName = $this->review->visit->visitType->visitGroup->study->name;

        AuthorizationTools::actAsAdmin(false);

        $answer = $this->get('api/studies/'.$studyName.'/visit-types/'.$visitTypeId.'/reviews/metadata');
        $answer->assertStatus(403);

    }

    public function testGetReviewFormFromUser(){

        $visitId = $this->review->visit->id;

        $userId = AuthorizationTools::actAsAdmin(false);
        $this->review->user_id = $userId;
        $this->review->save();

        AuthorizationTools::addRoleToUser($userId, Constants::ROLE_REVIEWER, $this->studyName);

        $answer = $this->get('api/studies/'.$this->studyName.'/visits/'.$visitId.'/reviews?userId='.$userId);
        dd($answer);


    }

}
