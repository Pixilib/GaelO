<?php

namespace Tests\Unit\TestRepositories;

use App\GaelO\Repositories\DicomSeriesRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

use App\Models\DicomSeries;
use App\Models\DicomStudy;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class DicomSeriesRepositoryTest extends TestCase
{
    private DicomSeriesRepository $orthancSeriesRepository;
    private DicomStudy $orthancStudy;

    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed');
        $this->orthancSeriesRepository = new DicomSeriesRepository(new DicomSeries());
        $this->orthancStudy = DicomStudy::factory()->create();
    }

    public function testAddSeries(){

        $this->orthancSeriesRepository->addSeries(
                '6b9e19d9-62094390-5f9ddb01-4a191ae7-9766b715',
                $this->orthancStudy->study_uid, null,
                null, null, null,
                null, null, null,
                null, null, null,
                50, '123456789', null,
                30, 30, null,
                null
            );

        $orthancSeries = DicomSeries::find('123456789');
        $this->assertEquals('6b9e19d9-62094390-5f9ddb01-4a191ae7-9766b715', $orthancSeries->orthanc_id);
        $this->assertEquals(30, $orthancSeries->disk_size);
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

    public function testGetRelatedVisitIdFromSeriesInstanceUid(){

        $dicomSeries = DicomSeries::factory()->count(5)->create();
        //Should work even for deleted series / studies
        $dicomSeries->first()->delete();
        $dicomSeries->first()->dicomStudy->delete();

        $seriesInstanceUID = $dicomSeries->pluck('series_uid')->toArray();
        $answer = $this->orthancSeriesRepository->getRelatedVisitIdFromSeriesInstanceUID($seriesInstanceUID, true);
        $this->assertEquals(5, sizeof($answer));
    }

    public function testGetSeriesOrthancIdsOfSeriesInstanceUids(){

        $dicomSeries = DicomSeries::factory()->count(5)->create();
        //Should work even for deleted series / studies if requested
        $dicomSeries->first()->delete();
        $dicomSeries->first()->dicomStudy->delete();

        $seriesInstanceUID = $dicomSeries->pluck('series_uid')->toArray();
        $answer = $this->orthancSeriesRepository->getSeriesOrthancIDsOfSeriesInstanceUIDs($seriesInstanceUID, true);
        $this->assertEquals(5, sizeof($answer));
        $answerNotTrashed = $this->orthancSeriesRepository->getSeriesOrthancIDsOfSeriesInstanceUIDs($seriesInstanceUID, false);
        $this->assertEquals(4, sizeof($answerNotTrashed));

    }

    public function testGetSeriesByStudyInstanceUidArray(){

        $dicomSeries = DicomSeries::factory()->count(10)->create();
        $studyInstanceUID1  = $dicomSeries->first()->study_instance_uid;
        $studyInstanceUID2 = $dicomSeries->last()->study_instance_uid;

        $dicomSeries->first->delete();
        $series = $this->orthancSeriesRepository->getDicomSeriesOfStudyInstanceUIDArray([$studyInstanceUID1, $studyInstanceUID2], false);
        $this->assertEquals(1, sizeof($series));
        $series = $this->orthancSeriesRepository->getDicomSeriesOfStudyInstanceUIDArray([$studyInstanceUID1, $studyInstanceUID2], true);
        $this->assertEquals(2, sizeof($series));
    }



}
