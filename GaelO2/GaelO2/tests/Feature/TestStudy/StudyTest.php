<?php

namespace Tests\Feature\TestStudy;

use App\GaelO\Constants\Constants;
use App\Models\DicomStudy;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;
use App\Models\Study;
use App\Models\Visit;
use App\Models\VisitType;
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
            'name'=>'NEWSTUDY',
            'code'=>'1234',
            'patientCodeLength' => 5
        ];

        $this->json('POST', '/api/studies', $payload)->assertNoContent(201);

    }

    public function testCreateStudyShouldFailBecauseNotAlfaNumerical() {
        AuthorizationTools::actAsAdmin(true);

        $payload = [
            'name'=>'NEWSTUDy',
            'code'=>'1234',
            'patientCodeLength' => 5
        ];

        $this->json('POST', '/api/studies', $payload)->assertStatus(400);

        $payload = [
            'name'=>'NEW STUDY',
            'code'=>'1234',
            'patientCodeLength' => 5
        ];

        $this->json('POST', '/api/studies', $payload)->assertStatus(400);

        $payload = [
            'name'=>'NEW.STUDY',
            'code'=>'1234',
            'patientCodeLength' => 5
        ];

        $this->json('POST', '/api/studies', $payload)->assertStatus(400);

    }

    public function testCreateAncillaryStudy(){
        AuthorizationTools::actAsAdmin(true);
        $study = Study::factory()->create();
        $payload = [
            'name'=>'NEWSTUDY',
            'code'=>'1234',
            'patientCodeLength' => 5,
            'ancillaryOf'=> $study->name
        ];
        $this->json('POST', '/api/studies', $payload)->assertStatus(201);
    }

    public function testCreateStudyForbiddenNotAdmin(){
        AuthorizationTools::actAsAdmin(false);
        $payload = [
            'name'=>'NEWSTUDY',
            'code'=>'1234',
            'patientCodeLength' => 5
        ];
        $this->json('POST', '/api/studies', $payload)->assertStatus(403);
    }

    public function testCreateAlreadyExistingStudy(){
        AuthorizationTools::actAsAdmin(true);
        $study = Study::factory()->count(2)->create()->first();
        $payload = [
            'name'=>$study->name,
            'code'=>'1234',
            'patientCodeLength' => 5
        ];
        $this->json('POST', '/api/studies', $payload)->assertStatus(409);
    }

    public function testCreateStudyWith0Length(){
        AuthorizationTools::actAsAdmin(true);
        $payload = [
            'name'=>'NEWSTUDY',
            'code'=>'1234',
            'patientCodeLength' => 0
        ];
        $this->json('POST', '/api/studies', $payload)->assertStatus(400);
    }

    public function testDeleteStudy(){
        AuthorizationTools::actAsAdmin(true);
        $study = Study::factory()->create();
        $this->json('DELETE', '/api/studies/'.$study->name, ['reason'=> 'study finished'])->assertSuccessful();

    }

    public function testDeleteStudyShouldFailNoReason(){
        AuthorizationTools::actAsAdmin(true);
        $study = Study::factory()->create();
        $this->json('DELETE', '/api/studies/'.$study->name)->assertStatus(400);

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

    public function testGetStudyDetails(){
        $userId = AuthorizationTools::actAsAdmin(true);
        $visitType = VisitType::factory()->create();
        AuthorizationTools::addRoleToUser($userId, Constants::ROLE_SUPERVISOR, $visitType->visitGroup->study->name);
        $answer = $this->json('GET', '/api/studies/'.$visitType->visitGroup->study->name.'/visit-types');
        $answer->assertStatus(200);
        $expectedKeys = [
            "id",
            "visitGroupId",
            "name",
            "order",
            "localFormNeeded",
            "qcProbability",
            "reviewProbability",
            "optional",
            "limitLowDays",
            "limitUpDays",
            "anonProfile",
            "dicomConstraints",
            "visitGroup"
        ];
        foreach($expectedKeys as $key) {
            $this->assertArrayHasKey($key, $answer[0]);
        }
    }

    public function testGetStudyDetailsShouldFailNotSupervisor(){
        AuthorizationTools::actAsAdmin(true);
        $visitType = VisitType::factory()->create();
        $this->json('GET', '/api/studies/'.$visitType->visitGroup->study->name.'/visit-types')->assertStatus(403);
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
        $payload = ['reason' => 'need new analysis'];
        $this->json('PATCH', '/api/studies/'.$studyName.'/reactivate', $payload)->assertNoContent(200);

    }

    public function testReactivateStudyShouldFailNoReason(){
        AuthorizationTools::actAsAdmin(true);
        $study =  Study::factory()->create();
        $studyName = $study->name;
        $study->delete();
        $payload = [];
        $this->json('PATCH', '/api/studies/'.$studyName.'/reactivate', $payload)->assertStatus(400);

    }

    public function testReactivateStudyForbiddenNotAdmin(){
        AuthorizationTools::actAsAdmin(false);
        $study = Study::factory()->create();
        $study->delete();
        $payload = ['reason'=> 'need new analysis'];
        $this->json('PATCH', '/api/studies/'.$study->name.'/reactivate', $payload)->assertStatus(403);
    }

    public function testIsKnownOrthancStudyIDForbiddenNotInvestigator(){
        AuthorizationTools::actAsAdmin(false);
        $study = Study::factory()->create();
        $studyName = $study->name;

        $this->json('GET', '/api/studies/'.$studyName.'/original-orthanc-study-id/WrongOrthancID')->assertStatus(403);
    }

    public function testIsKnownOriginalOrthancStudyIDYes(){

        $visit = Visit::factory()->create();
        $studyName = $visit->patient->study_name;

        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_INVESTIGATOR, $studyName);

        $dicomStudyInstance = DicomStudy::factory()
            ->visitId($visit->id)
            ->create();

        AuthorizationTools::addRoleToUser(1, Constants::ROLE_INVESTIGATOR, $studyName);
        $this->json('GET', '/api/studies/'.$studyName.'/original-orthanc-study-id/'.$dicomStudyInstance->anon_from_orthanc_id)->assertStatus(200);
        $dicomStudyInstance->delete();
        $this->json('GET', '/api/studies/'.$studyName.'/original-orthanc-study-id/'.$dicomStudyInstance->anon_from_orthanc_id)->assertStatus(404);
    }

    public function testGetStudyStatistics(){
        $dicomStudy = DicomStudy::factory()
            ->create();
        AuthorizationTools::actAsAdmin(true);
        $answer = $this->json('GET', '/api/studies/'.$dicomStudy->visit->patient->study_name.'/statistics');
        $answer->assertStatus(200);

    }

    public function testGetStudyStatisticsShouldFailNotAdmin(){
        $dicomStudy = DicomStudy::factory()
            ->create();
        AuthorizationTools::actAsAdmin(false);
        $answer = $this->json('GET', '/api/studies/'.$dicomStudy->visit->patient->study_name.'/statistics');
        $answer->assertStatus(403);

    }


}
