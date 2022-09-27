<?php

namespace Tests\Feature\TestDicoms;

use App\GaelO\Constants\Constants;
use App\Models\DicomStudy;
use App\Models\Patient;
use Tests\TestCase;
use App\Models\Visit;
use App\Models\ReviewStatus;
use App\Models\Study;
use App\Models\VisitGroup;
use App\Models\VisitType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\AuthorizationTools;

class GetDicomsStudiesFromStudyTest extends TestCase
{

    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed');

        $study = Study::factory()->create();
        $patient = Patient::factory()->studyName($study->name)->create();
        $visitGroup = VisitGroup::factory()->studyName($study->name)->create();
        $visitType = VisitType::factory()->visitGroupId($visitGroup->id)->count(2)->create();

        $visitType->each(function ($visitType, $key) use ($study, $patient) {
            $visit = Visit::factory()->patientId($patient->id)->visitTypeId($visitType->id)->create();
            DicomStudy::factory()->visitId($visit->id)->create();
            DicomStudy::factory()->visitId($visit->id)->create()->delete();
            ReviewStatus::factory()->studyName($study->name)->visitId($visit->id)->create();
        });

        $this->studyName = $study->name;
    }


    public function testGetDicomStudiesFromStudy()
    {

        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_SUPERVISOR, $this->studyName);
        $answer = $this->json('GET', 'api/studies/' . $this->studyName . '/dicom-studies');
        $answer->assertStatus(200);
        //Should be 2 studies as do not include deleted
        $results = json_decode($answer->content(), true);
        $this->assertEquals(2, sizeof($results));
    }

    public function testGetDicomsFromStudyShouldFailNotSupervisor()
    {

        AuthorizationTools::actAsAdmin(false);
        $answer = $this->json('GET', 'api/studies/' . $this->studyName . '/dicom-studies');
        $answer->assertStatus(403);
    }

    public function testGetDicomStudyWithTrashed()
    {
        $userId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($userId, Constants::ROLE_SUPERVISOR, $this->studyName);
        $answer = $this->json('GET', 'api/studies/' . $this->studyName . '/dicom-studies?withTrashed')->assertStatus(200);
        //Should be 4 studies as it include deleted
        $results = json_decode($answer->content(), true);
        $this->assertEquals(4, sizeof($results));
    }

}
