<?php

use App\GaelO\Constants\Constants;
use App\Models\Patient;
use App\Models\Study;
use App\Models\Visit;
use App\Models\VisitGroup;
use App\Models\VisitType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\AuthorizationTools;
use Tests\TestCase;
use Illuminate\Support\Facades\Storage;

class FileVisitTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed');
        Storage::fake();
    }

    private function createVisit()
    {
        $study = Study::factory()->name('TEST')->create();
        $patient = Patient::factory()->studyName($study->name)->create();
        $visitGroup = VisitGroup::factory()->studyName($study->name)->name('FDG')->create();
        $visitType  = VisitType::factory()->visitGroupId($visitGroup->id)->name('PET_0')->localFormNeeded()->create();
        $path = $study->name . '/' . 'attached_visit_file' . '/' . 'visit_1_41.csv';
        $visit = Visit::factory()->patientId($patient->id)->visitTypeId($visitType->id)->sentFiles(['41' => $path])->create();
        Storage::put($path, "testcontent");
        return [
            'studyName' => $study->name,
            'visitId' => $visit->id,
            'centerCode' => $patient->center_code
        ];
    }

    public function testGetFileOfVisit()
    {
        $currentVisit = $this->createVisit();
        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_INVESTIGATOR, $currentVisit['studyName']);
        AuthorizationTools::addAffiliatedCenter($currentUserId, $currentVisit['centerCode']);
        $response = $this->get('api/visits/' . $currentVisit['visitId'] . '/files/41?role=Investigator&studyName=TEST');
        $response->assertSuccessful();
    }

    public function testGetFileOfVisitShouldFailNoRole()
    {
        $currentVisit = $this->createVisit();
        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_INVESTIGATOR, $currentVisit['studyName']);
        AuthorizationTools::addAffiliatedCenter($currentUserId, $currentVisit['centerCode']);
        $response = $this->get('api/visits/' . $currentVisit['visitId'] . '/files/41?role=Supervisor&studyName=TEST');
        $response->assertStatus(403);
    }

    public function testDeleteFileOfVisit()
    {

    }

    public function testDeleteFileOfVisitShouldFailNoRole()
    {

    }
}
