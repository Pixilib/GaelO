<?php

namespace Tests\Feature\TestDicoms;

use App\GaelO\Adapters\FileCacheAdapter;
use App\GaelO\Constants\Constants;
use App\GaelO\Services\FileCacheService;
use App\Models\DicomSeries;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\AuthorizationTools;
use Tests\TestCase;

class DicomMetadataTest extends TestCase
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
        $fileCacheAdapter = new FileCacheService(new FileCacheAdapter());
        $fileCacheAdapter->storeDicomMetadata($this->dicomSeries->dicomStudy->study_uid, json_encode(['study' => 'value']));
        $fileCacheAdapter->storeDicomMetadata($this->dicomSeries->series_uid, json_encode(['series' => 'value2']));
        $fileCacheAdapter->storeSeriesPreview($this->dicomSeries->series_uid, 0, 'plaintext');
    }

    public function testGetDicomStudiesMetadata()
    {
        $userId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($userId, Constants::ROLE_SUPERVISOR, $this->studyName);

        $response = $this->get('api/dicom-studies/' . $this->dicomSeries->dicomStudy->study_uid . '/metadata?role=Supervisor&studyName=' . $this->studyName);
        $response->assertStatus(200);
        $response->assertJsonIsObject();
    }

    public function testGetDicomStudiesMetadataShouldFailNoRole()
    {
        AuthorizationTools::actAsAdmin(false);

        $response = $this->get('api/dicom-studies/' . $this->dicomSeries->dicomStudy->study_uid . '/metadata?role=Supervisor&studyName=' . $this->studyName);
        $response->assertStatus(403);
    }

    public function testGetDicomSeriesMetadata()
    {
        $userId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($userId, Constants::ROLE_SUPERVISOR, $this->studyName);

        $response = $this->get('api/dicom-series/' . $this->dicomSeries->series_uid . '/metadata?role=Supervisor&studyName=' . $this->studyName);
        $response->assertStatus(200);
        $response->assertJsonIsObject();
    }

    public function testGetDicomSeriesMetadataShouldFailNoRole()
    {
        AuthorizationTools::actAsAdmin(false);

        $response = $this->get('api/dicom-series/' . $this->dicomSeries->series_uid . '/metadata?role=Supervisor&studyName=' . $this->studyName);
        $response->assertStatus(403);
    }

    public function testGetDicomSeriesPreview()
    {
        $userId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($userId, Constants::ROLE_SUPERVISOR, $this->studyName);

        $response = $this->get('api/dicom-series/' . $this->dicomSeries->series_uid . '/previews/0?role=Supervisor&studyName=' . $this->studyName);
        $response->assertStatus(200);
        $response->assertContent("plaintext");
    }
}
