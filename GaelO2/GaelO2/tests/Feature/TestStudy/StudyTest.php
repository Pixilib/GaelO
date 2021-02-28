<?php

namespace Tests\Feature\TestStudy;

use App\GaelO\Constants\Constants;
use App\Models\DicomStudy;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;
use App\Models\Study;
use App\Models\Visit;
use Tests\AuthorizationTools;

class StudyTest extends TestCase
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

    public function testCreateStudy() {
        AuthorizationTools::actAsAdmin(true);

        $payload = [
            'studyName'=>'NEWSTUDY',
            'patientCodePrefix'=>'1234'
        ];

        $this->json('POST', '/api/studies', $payload)->assertNoContent(201);

    }

    public function testCreateStudyShouldFailBecauseNotAlfaNumerical() {
        AuthorizationTools::actAsAdmin(true);

        $payload = [
            'studyName'=>'NEWSTUDy',
            'patientCodePrefix'=>'1234'
        ];

        $this->json('POST', '/api/studies', $payload)->assertStatus(400);

        $payload = [
            'studyName'=>'NEW STUDY',
            'patientCodePrefix'=>'1234'
        ];

        $this->json('POST', '/api/studies', $payload)->assertStatus(400);

        $payload = [
            'studyName'=>'NEW.STUDY',
            'patientCodePrefix'=>'1234'
        ];

        $this->json('POST', '/api/studies', $payload)->assertStatus(400);

    }

    public function testCreateStudyForbiddenNotAdmin(){
        AuthorizationTools::actAsAdmin(false);
        $payload = [
            'studyName'=>'NEWSTUDY',
            'patientCodePrefix'=>'1234'
        ];
        $this->json('POST', '/api/studies', $payload)->assertStatus(403);
    }

    public function testCreateAlreadyExistingStudy(){
        AuthorizationTools::actAsAdmin(true);
        $study = Study::factory()->count(2)->create()->first();
        $payload = [
            'studyName'=>$study->name,
            'patientCodePrefix'=>'1234'
        ];
        $this->json('POST', '/api/studies', $payload)->assertStatus(409);
    }

    public function testDeleteStudy(){
        AuthorizationTools::actAsAdmin(true);
        $study = Study::factory()->create();
        $response = $this->json('DELETE', '/api/studies/'.$study->name)->assertSuccessful();

    }

    public function testGetStudiesWithDetails(){
        AuthorizationTools::actAsAdmin(true);
        Study::factory()->count(2)->create();
        $response = $this->json('GET', '/api/studies?expand')->assertSuccessful();
        $response->assertJsonCount(2);

    }

    public function testGetStudies(){
        AuthorizationTools::actAsAdmin(true);
        Study::factory()->create();
        $this->json('GET', '/api/studies')->assertJsonCount(1);
    }

    public function testGetStudiesForbiddenNotAdmin(){
        AuthorizationTools::actAsAdmin(false);
        Study::factory()->create();
        $this->json('GET', '/api/studies')->assertStatus(403);
    }

    public function testGetDeletedStudies(){
        AuthorizationTools::actAsAdmin(true);
        $study =  Study::factory()->create();
        $study->delete();
        $response = $this->json('GET', '/api/studies')->content();
        $response = json_decode($response, true);
        $this->assertTrue($response[0]['deleted']);
    }

    public function testReactivateStudy(){
        AuthorizationTools::actAsAdmin(true);
        $study =  Study::factory()->create();
        $studyName = $study->name;
        $study->delete();
        $payload = [];
        $this->json('PATCH', '/api/studies/'.$studyName.'/reactivate', $payload)->assertNoContent(200);

    }

    public function testReactivateStudyForbiddenNotAdmin(){
        AuthorizationTools::actAsAdmin(false);
        $study = Study::factory()->create();
        $study->delete();
        $payload = [];
        $this->json('PATCH', '/api/studies/'.$study->name.'/reactivate', $payload)->assertStatus(403);
    }

    public function testIsKnownOrthancStudyIDForbiddenNotInvestigator(){
        AuthorizationTools::actAsAdmin(false);
        $study = Study::factory()->create();
        $studyName = $study->name;

        $this->json('GET', '/api/studies/'.$studyName.'/orthanc-study-id/WrongOrthancID')->assertStatus(403);
    }

    public function testIsKnownOrthancStudyIDYes(){

        $visit = Visit::factory()->create();
        $studyName = $visit->visitType->visitGroup->study->name;

        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_INVESTIGATOR, $studyName);

        $dicomStudyInstance = DicomStudy::factory()
            ->visitId($visit->id)
            ->orthancStudy('7d2804c1-a17e7902-9a04d3fd-03e67d58-5ff3b85f')
            ->create();

        AuthorizationTools::addRoleToUser(1, Constants::ROLE_INVESTIGATOR, $studyName);
        $this->json('GET', '/api/studies/'.$studyName.'/orthanc-study-id/7d2804c1-a17e7902-9a04d3fd-03e67d58-5ff3b85f')->assertStatus(200);
        $dicomStudyInstance->delete();
        $this->json('GET', '/api/studies/'.$studyName.'/orthanc-study-id/7d2804c1-a17e7902-9a04d3fd-03e67d58-5ff3b85g')->assertStatus(404);
    }


}
