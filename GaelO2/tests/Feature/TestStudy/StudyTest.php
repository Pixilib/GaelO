<?php

namespace Tests\Feature\TestStudy;

use App\GaelO\Constants\Constants;
use App\Models\DicomStudy;
use Tests\TestCase;
use App\Models\Study;
use App\Models\Visit;
use App\Models\VisitType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\AuthorizationTools;

class StudyTest extends TestCase
{

    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed');
    }

    public function testGetStudiesWithDetails()
    {
        AuthorizationTools::actAsAdmin(true);
        VisitType::factory()->count(4)->create();
        $response = $this->json('GET', '/api/studies?expand')->assertSuccessful();
        $response->assertJsonCount(4);
        $answer = json_decode($response->content(), true);
        foreach ($answer as $studyName => $details) {
            $this->assertArrayHasKey('visitGroups', $details);
            $this->assertArrayHasKey('visitTypes', $details['visitGroups'][0]);
        }
    }

    public function getStudy()
    {
        AuthorizationTools::actAsAdmin(true);
        $study = Study::factory()->create();
        $answer = $this->json('GET', '/api/studies/' + $study->name);
        $answer->assertSuccessful();
        $data = json_decode($answer->content(), true);
        $this->assertArrayHasKey('visitGroups', $data);
    }

    public function getStudyShouldFailNotAdmin()
    {
        AuthorizationTools::actAsAdmin(false);
        $study = Study::factory()->create();
        $this->json('GET', '/api/studies/' + $study->name)->assertStatus(403);
    }

    public function testGetStudies()
    {
        AuthorizationTools::actAsAdmin(true);
        $studies = Study::factory()->count(2)->create();
        $studies->first()->delete();
        $this->json('GET', '/api/studies')->assertJsonCount(1);
    }

    public function testGetStudiesWithTrashed()
    {
        AuthorizationTools::actAsAdmin(true);
        $studies = Study::factory()->count(2)->create();
        $studies->first()->delete();
        $this->json('GET', '/api/studies?withTrashed')->assertJsonCount(2);
    }


    public function testGetStudiesForbiddenNotAdmin()
    {
        AuthorizationTools::actAsAdmin(false);
        Study::factory()->create();
        $this->json('GET', '/api/studies')->assertStatus(403);
    }

    public function testGetStudyVisitTypes()
    {
        $userId = AuthorizationTools::actAsAdmin(true);
        $visitType = VisitType::factory()->create();
        AuthorizationTools::addRoleToUser($userId, Constants::ROLE_SUPERVISOR, $visitType->visitGroup->study->name);
        $answer = $this->json('GET', '/api/studies/' . $visitType->visitGroup->study->name . '/visit-types');
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
        foreach ($expectedKeys as $key) {
            $this->assertArrayHasKey($key, $answer[0]);
        }
    }


    public function testGetStudyVisitTypesOfAncillaryStudy()
    {
        $userId = AuthorizationTools::actAsAdmin(true);
        $visitType = VisitType::factory()->create();
        $originalStudyName = $visitType->visitGroup->study->name;
        $ancillaryStudy = Study::factory()->ancillaryOf($originalStudyName)->create();
        AuthorizationTools::addRoleToUser($userId, Constants::ROLE_SUPERVISOR, $ancillaryStudy->name);
        $answer = $this->json('GET', '/api/studies/' . $ancillaryStudy->name . '/visit-types');
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
        foreach ($expectedKeys as $key) {
            $this->assertArrayHasKey($key, $answer[0]);
        }
    }

    public function testGetStudyVisitTypesShouldFailNotSupervisor()
    {
        AuthorizationTools::actAsAdmin(true);
        $visitType = VisitType::factory()->create();
        $this->json('GET', '/api/studies/' . $visitType->visitGroup->study->name . '/visit-types')->assertStatus(403);
    }

    public function testIsKnownOrthancStudyIDForbiddenNotInvestigator()
    {
        AuthorizationTools::actAsAdmin(false);
        $study = Study::factory()->create();
        $studyName = $study->name;

        $this->json('GET', '/api/studies/' . $studyName . '/original-orthanc-study-id/WrongOrthancID')->assertStatus(403);
    }

    public function testIsKnownOriginalOrthancStudyIDYes()
    {

        $visit = Visit::factory()->create();
        $studyName = $visit->patient->study_name;

        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_INVESTIGATOR, $studyName);

        $dicomStudyInstance = DicomStudy::factory()
            ->visitId($visit->id)
            ->create();

        AuthorizationTools::addRoleToUser(1, Constants::ROLE_INVESTIGATOR, $studyName);
        $this->json('GET', '/api/studies/' . $studyName . '/original-orthanc-study-id/' . $dicomStudyInstance->anon_from_orthanc_id)->assertStatus(200);
        $dicomStudyInstance->delete();
        $this->json('GET', '/api/studies/' . $studyName . '/original-orthanc-study-id/' . $dicomStudyInstance->anon_from_orthanc_id)->assertStatus(404);
    }

    public function testGetStudyStatistics()
    {
        $dicomStudy = DicomStudy::factory()
            ->create();
        AuthorizationTools::actAsAdmin(true);
        $answer = $this->json('GET', '/api/studies/' . $dicomStudy->visit->patient->study_name . '/statistics');
        $answer->assertStatus(200);
    }

    public function testGetStudyStatisticsShouldFailNotAdmin()
    {
        $dicomStudy = DicomStudy::factory()
            ->create();
        AuthorizationTools::actAsAdmin(false);
        $answer = $this->json('GET', '/api/studies/' . $dicomStudy->visit->patient->study_name . '/statistics');
        $answer->assertStatus(403);
    }
}
