<?php

namespace Tests\Unit\TestRepositories;

use App\GaelO\Repositories\DicomSeriesRepository;
use App\GaelO\Services\StoreObjects\OrthancSeries;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

use App\Models\DicomSeries;
use App\Models\DicomStudy;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class DicomSeriesRepositoryTest extends TestCase
{
    private DicomSeriesRepository $orthancSeriesRepository;

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
        $this->orthancSeriesRepository = new DicomSeriesRepository(new DicomSeries());
        $this->orthancStudy = DicomStudy::factory()->create();
    }

    public function testAddSeries(){

        $this->orthancSeriesRepository->addSeries(
                '6b9e19d9-62094390-5f9ddb01-4a191ae7-9766b715',
                $this->orthancStudy->study_uid, null,
                null, null, null,
                null, null, null,
                null, null, null, null,
                50, '123456789', null,
                30, 30, null,
                null
            );

        $orthancSeries = DicomSeries::find('123456789');
        $this->assertEquals('6b9e19d9-62094390-5f9ddb01-4a191ae7-9766b715', $orthancSeries->orthanc_id);
        $this->assertEquals(30, $orthancSeries->disk_size);
    }

    public function testUpdateSeries(){
        $orthancSeries = DicomSeries::factory()->create();

        $this->orthancSeriesRepository->updateSeries(
            $orthancSeries->orthanc_id, null,
            null, null, 'newSeriesDescription',
            null, null, null,
            null, null, null, null,
            50, $orthancSeries->series_uid, null,
            50, 50, null,
            null
        );

        $orthancSeries2 = DicomSeries::find($orthancSeries->series_uid);
        $this->assertEquals('newSeriesDescription', $orthancSeries2->series_description);
        $this->assertEquals(50, $orthancSeries2->disk_size);
    }

    public function testIsExistingSeriesId(){
        $orthancSeries = DicomSeries::factory()->create();
        $existing = $this->orthancSeriesRepository->isExistingSeriesInstanceUID($orthancSeries->series_uid);
        $this->assertTrue($existing);
    }

    public function testGetSeriesBySeriesInstanceUid(){

        $orthancSeries = DicomSeries::factory()->create();

        $seriesEntity = $this->orthancSeriesRepository->getSeries($orthancSeries->series_uid, false);
        $this->assertNotNull($seriesEntity);

        $orthancSeries->delete();
        $seriesEntity2 = $this->orthancSeriesRepository->getSeries($orthancSeries->series_uid, true);
        $this->assertNotNull($seriesEntity2);

        $this->expectException(ModelNotFoundException::class);
        $seriesEntity2 = $this->orthancSeriesRepository->getSeries($orthancSeries->series_uid, false);
    }

    public function testReactivateSeriesOfOrthancStudyId(){
        $orthancSeries = DicomSeries::factory()->studyInstanceUID($this->orthancStudy->study_uid)->count(5)->create();
        $orthancSeries->each( function($item, $key){ $item->delete(); } );
        $this->assertEquals(0, DicomSeries::get()->count());

        $this->orthancSeriesRepository->reactivateSeriesOfStudyInstanceUID($this->orthancStudy->study_uid);
        $this->assertEquals(5, DicomSeries::get()->count());
    }

    public function testGetRelatedVisitIdFromSeriesInstanceUid(){

        $dicomSeries = DicomSeries::factory()->count(5)->create();
        //Should work even for deleted series / studies
        $dicomSeries->first()->delete();
        $dicomSeries->first()->dicomStudy->delete();

        $seriesInstanceUID = $dicomSeries->pluck('series_uid')->toArray();
        $answer = $this->orthancSeriesRepository->getRelatedVisitIdFromSeriesInstanceUID($seriesInstanceUID);
        $this->assertEquals(5, sizeof($answer));
    }

    public function testGetSeriesOrthancIdOfSeriesInstanceUid(){

        $dicomSeries = DicomSeries::factory()->count(5)->create();
        //Should work even for deleted series / studies
        $dicomSeries->first()->delete();
        $dicomSeries->first()->dicomStudy->delete();

        $seriesInstanceUID = $dicomSeries->pluck('series_uid')->toArray();
        $answer = $this->orthancSeriesRepository->getSeriesOrthancIDOfSeriesInstanceUID($seriesInstanceUID);
        $this->assertEquals(5, sizeof($answer));

    }

    public function testGetSeriesByStudyInstanceUidArray(){

        $dicomSeries = DicomSeries::factory()->count(10)->create();
        $studyInstanceUID1  = $dicomSeries->first()->study_uid;
        $studyInstanceUID2 = $dicomSeries->last()->study_uid;

        $dicomSeries->first->delete();
        $series = $this->orthancSeriesRepository->getDicomSeriesOfStudyInstanceUIDArray([$studyInstanceUID1, $studyInstanceUID2], false);
        $this->assertEquals(1, sizeof($series));
        $series = $this->orthancSeriesRepository->getDicomSeriesOfStudyInstanceUIDArray([$studyInstanceUID1, $studyInstanceUID2], true);
        $this->assertEquals(2, sizeof($series));
    }



}
