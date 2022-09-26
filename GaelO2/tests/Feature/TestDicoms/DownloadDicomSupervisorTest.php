<?php

namespace Tests\Feature\TestDicoms;

use Tests\TestCase;
use App\Models\DicomStudy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\AuthorizationTools;

class DownloadDicomSupervisorTest extends TestCase
{

    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed');
        $this->dicomStudy = DicomStudy::factory()->create();
        $this->visitTypeId = $this->dicomStudy->visit->visitType->id;
        $this->studyName = $this->dicomStudy->visit->patient->study_name;
    }

    public function testGetDicomsSupervisorFile()
    {
        AuthorizationTools::actAsAdmin(false);
        $this->post('api/studies/' . $this->studyName . '/dicom-series/file', [ 'seriesInstanceUID'=>['125.156'] ])->assertStatus(400);
    }
}
