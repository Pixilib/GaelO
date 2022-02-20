<?php

namespace Tests\Feature\TestDicoms;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;
use App\Models\DicomStudy;
use Tests\AuthorizationTools;

class DownloadDicomSupervisorTest extends TestCase
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
        $this->visitTypeId = $this->dicomStudy->visit->visitType->id;
        $this->studyName = $this->dicomStudy->visit->patient->study_name;
    }

    public function testGetDicomsSupervisorFile()
    {
        AuthorizationTools::actAsAdmin(false);
        $this->post('api/studies/' . $this->studyName . '/dicom-series/file', [ 'seriesInstanceUID'=>['125.156'] ])->assertStatus(400);
    }
}
