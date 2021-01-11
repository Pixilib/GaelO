<?php

namespace Tests\Unit\TestRepositories;

use App\GaelO\Repositories\OrthancSeriesRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

use App\Models\OrthancSeries;
use App\Models\OrthancStudy;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class OrthancSeriesRepositoryTest extends TestCase
{
    private OrthancSeriesRepository $orthancSeriesRepository;

    use DatabaseMigrations {
        runDatabaseMigrations as baseRunDatabaseMigrations;
    }

    use RefreshDatabase;

    public function runDatabaseMigrations()
    {
        $this->baseRunDatabaseMigrations();
        $this->artisan('db:seed');
    }


    protected function setUp(): void
    {
        parent::setUp();
        $this->orthancSeriesRepository = new OrthancSeriesRepository(new OrthancSeries());
        $this->orthancStudy = OrthancStudy::factory()->create();
    }

    public function testAddSeries(){

        $this->orthancSeriesRepository->addSeries(
                '6b9e19d9-62094390-5f9ddb01-4a191ae7-9766b715',
                $this->orthancStudy->orthanc_id, null,
                null, null, null,
                null, null, null,
                null, null, null, null,
                50, '123456789', null,
                30, 30, null,
                null
            );

        $orthancSeries = OrthancSeries::find('6b9e19d9-62094390-5f9ddb01-4a191ae7-9766b715');
        $this->assertEquals('6b9e19d9-62094390-5f9ddb01-4a191ae7-9766b715', $orthancSeries->orthanc_id);
        $this->assertEquals(30, $orthancSeries->disk_size);
    }

    public function testUpdateSeries(){
        $orthancSeries = OrthancSeries::factory()->create();

        $this->orthancSeriesRepository->updateSeries(
            $orthancSeries->orthanc_id, $orthancSeries->orthanc_study_id, null,
            null, null, 'newSeriesDescription',
            null, null, null,
            null, null, null, null,
            50, '12345698.654.65', null,
            50, 50, null,
            null
        );

        $orthancSeries2 = OrthancSeries::find($orthancSeries->orthanc_id);
        $this->assertEquals('newSeriesDescription', $orthancSeries2->series_description);
        $this->assertEquals(50, $orthancSeries2->disk_size);
    }

    public function testIsExistingSeriesId(){
        $orthancSeries = OrthancSeries::factory()->create();
        $existing = $this->orthancSeriesRepository->isExistingOrthancSeriesID($orthancSeries->orthanc_id);
        $this->assertTrue($existing);
    }

    public function testGetSeriesBySeriesInstanceUid(){

        $orthancSeries = OrthancSeries::factory()->create();

        $seriesEntity = $this->orthancSeriesRepository->getSeriesBySeriesInstanceUID($orthancSeries->series_uid, false);
        $this->assertNotNull($seriesEntity);

        $orthancSeries->delete();
        $seriesEntity2 = $this->orthancSeriesRepository->getSeriesBySeriesInstanceUID($orthancSeries->series_uid, true);
        $this->assertNotNull($seriesEntity2);

        $this->expectException(ModelNotFoundException::class);
        $seriesEntity2 = $this->orthancSeriesRepository->getSeriesBySeriesInstanceUID($orthancSeries->series_uid, false);
    }

    public function testReactivateSeriesOfOrthancStudyId(){
        $orthancSeries = OrthancSeries::factory()->orthancStudyId($this->orthancStudy->orthanc_id)->count(5)->create();
        $orthancSeries->each( function($item, $key){ $item->delete(); } );
        $this->assertEquals(0, OrthancSeries::get()->count());

        $this->orthancSeriesRepository->reactivateSeriesOfOrthancStudyID($this->orthancStudy->orthanc_id);
        $this->assertEquals(5, OrthancSeries::get()->count());
    }



}
