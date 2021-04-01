<?php

namespace Tests\Feature\TestDicoms;

use App\GaelO\Constants\Constants;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;
use App\Models\DicomStudy;
use Tests\AuthorizationTools;

class DicomsStudyFromStudyTest extends TestCase
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
        $this->dicomStudy = DicomStudy::factory()->create();
        $this->studyName = $this->dicomStudy->visit->visitType->visitGroup->study_name;
    }

    public function testGetDicomStudy()
    {
        $userId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($userId, Constants::ROLE_SUPERVISOR, $this->studyName);
        $this->json('GET', 'api/studies/' . $this->studyName . '/dicom-studies')->assertStatus(200);
    }


    public function testGetDicomStudyWithTrashed()
    {
        $userId = AuthorizationTools::actAsAdmin(false);
        $this->dicomStudy->delete();
        $this->dicomStudy->save();
        AuthorizationTools::addRoleToUser($userId, Constants::ROLE_SUPERVISOR, $this->studyName);
        $this->json('GET', 'api/studies/' . $this->studyName . '/dicom-studies?withTrashed')->assertStatus(200);
    }

    public function testGetDicomStudyShouldFailNotSupervisor()
    {
        AuthorizationTools::actAsAdmin(false);
        $this->json('GET', 'api/studies/' . $this->studyName . '/dicom-studies?withTrashed')->assertStatus(403);
    }
}
