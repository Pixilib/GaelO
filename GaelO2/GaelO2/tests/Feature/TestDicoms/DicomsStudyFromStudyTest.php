<?php

namespace Tests\Feature\TestDicoms;

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
        AuthorizationTools::actAsAdmin(false);
        dd($this->json('GET', 'api/studies/' . $this->studyName . '/dicom-studies?expand'));
    }
}
