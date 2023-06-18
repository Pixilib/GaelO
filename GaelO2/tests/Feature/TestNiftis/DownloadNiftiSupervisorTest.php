<?php

namespace Tests\Feature\TestDicoms;

use App\GaelO\Constants\Constants;
use App\Models\DicomSeries;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\AuthorizationTools;

class DownloadNiftiSupervisorTest extends TestCase
{

    use RefreshDatabase;
    private DicomSeries $dicomSeries;
    private string $studyName;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed');
        $this->dicomSeries = DicomSeries::factory()->create();
        $this->studyName = $this->dicomSeries->dicomStudy->visit->patient->study_name;
    }

    public function testGetNiftiSupervisorFileShouldFailNoSupervisorRole()
    {
        AuthorizationTools::actAsAdmin(false);
        $this->get('api/dicom-series/'.$this->dicomSeries->series_uid.'/nifti?studyName='.$this->studyName)->assertStatus(403);
    }

    public function testGetNiftiSupervisorFile()
    {
        $userId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($userId, Constants::ROLE_SUPERVISOR, $this->studyName);
        $response = $this->get('api/dicom-series/'.$this->dicomSeries->series_uid.'/nifti?studyName='.$this->studyName);
        $response->assertStatus(200);
    }
}
