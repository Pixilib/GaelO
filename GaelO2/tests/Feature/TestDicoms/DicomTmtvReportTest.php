<?php

namespace Tests\Feature\TestDicoms;

use App\GaelO\Adapters\FileCacheAdapter;
use App\GaelO\Constants\Constants;
use App\GaelO\Services\FileCacheService;
use App\Models\DicomSeries;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\AuthorizationTools;
use Tests\TestCase;

class DicomTmtvReportTest extends TestCase
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
        $fileCacheAdapter->storeTmtvResults($this->dicomSeries->series_uid, '41', 'a binary payload');
        $fileCacheAdapter->storeTmtvPreview($this->dicomSeries->series_uid, '41', json_encode(['tmtv' => '41']));
    }

    public function testGetDicomTmtvPreview()
    {
        $userId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($userId, Constants::ROLE_SUPERVISOR, $this->studyName);

        $response = $this->get('api/dicom-series/' . $this->dicomSeries->series_uid . '/tmtv-report/preview?methodology=41&role=Supervisor&studyName=' . $this->studyName);
        $response->assertStatus(200);
        $response->assertJsonIsObject();
    }

    public function testGetDicomTmtvPreviewShouldFailNoRole()
    {
        AuthorizationTools::actAsAdmin(false);

        $response = $this->get('api/dicom-series/' . $this->dicomSeries->series_uid . '/tmtv-report/preview?methodology=41&role=Supervisor&studyName=' . $this->studyName);
        $response->assertStatus(403);
    }

    public function testGetDicomTmtvResults()
    {
        $userId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($userId, Constants::ROLE_SUPERVISOR, $this->studyName);

        $response = $this->get('api/dicom-series/' . $this->dicomSeries->series_uid . '/tmtv-report/stats?methodology=41&role=Supervisor&studyName=' . $this->studyName);
        $response->assertStatus(200);
    }

    public function testGetDicomTmtvResultsShouldFailNoRole()
    {
        AuthorizationTools::actAsAdmin(false);

        $response = $this->get('api/dicom-series/' . $this->dicomSeries->series_uid . '/tmtv-report/stats?methodology=41&role=Supervisor&studyName=' . $this->studyName);
        $response->assertStatus(403);
    }
}
