<?php

namespace Tests\Unit\TestRepositories;

use App\GaelO\Repositories\DicomStudyRepository;
use App\Models\DicomSeries;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

use App\Models\DicomStudy;
use App\Models\Study;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class DicomStudyRepositoryTest extends TestCase
{
    private DicomStudyRepository $dicomStudyRepository;

    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed');
        $this->dicomStudyRepository = new DicomStudyRepository(new DicomStudy());
    }


    public function testReactivateByStudyInstanceUid()
    {
        $orthancStudy = DicomStudy::factory()->create();
        $orthancStudy->delete();
        $this->assertEquals(0, DicomStudy::get()->count());
        $this->dicomStudyRepository->reactivateByStudyInstanceUID($orthancStudy->study_uid);
        $this->assertEquals(1, DicomStudy::get()->count());
    }

    public function testAddStudy()
    {

        $this->dicomStudyRepository->addStudy(
            '6b9e19d9-62094390-5f9ddb01-4a191ae7-9766b715',
            Visit::factory()->create()->id,
            User::factory()->create()->id,
            '2020-01-01',
            null,
            null,
            '6b9e19d9-62094390-5f9ddb01-4a191ae7-9766b716',
            '123.5698.32',
            null,
            '6b9e19d9-62094390-5f9ddb01-4a191ae7-9766b717',
            null,
            null,
            3,
            1500,
            300,
            600
        );

        $orthancStudyEntity = DicomStudy::find('123.5698.32');
        $this->assertEquals(3, $orthancStudyEntity->number_of_series);
    }

    public function testIsExistingOriginalOrthancStudyId()
    {

        $orthancStudy = DicomStudy::factory()->create();
        //2 study are created when factory of patient and visit type
        $studyName = $orthancStudy->visit->patient->study_name;
        $studyName2 = Study::factory()->create();

        $answer  = $this->dicomStudyRepository->isExistingOriginalOrthancStudyID($orthancStudy->anon_from_orthanc_id, $studyName);
        $answer2  = $this->dicomStudyRepository->isExistingOriginalOrthancStudyID($orthancStudy->anon_from_orthanc_id, $studyName2);
        //One study should be true, the other false
        $this->assertTrue($answer);
        $this->assertFalse($answer2);
    }

    public function testIsExistingOriginalOrthancStudyIdShouldNotIncludeDeleteVisit()
    {

        $orthancStudy = DicomStudy::factory()->create();
        //2 study are created when factory of patient and visit type
        $studyName = $orthancStudy->visit->patient->study_name;

        //Delete Parent Visit
        $orthancStudy->visit->delete();

        $answer  = $this->dicomStudyRepository->isExistingOriginalOrthancStudyID($orthancStudy->anon_from_orthanc_id, $studyName);
        //One study should be true, the other false
        $this->assertFalse($answer);
    }

    public function testGetStudyOrthancIdFromVisit()
    {
        Visit::factory()->count(5)->create();
        $orthancStudy = DicomStudy::factory()->count(5)->create();
        $studyOrthancId = $this->dicomStudyRepository->getStudyInstanceUidFromVisit($orthancStudy->last()->visit_id);

        $this->assertEquals($orthancStudy->last()->study_uid, $studyOrthancId);
    }

    public function testIsExisitingDicomStudyForVisit()
    {
        DicomStudy::factory()->create();
        $visitId = Visit::get()->first()->id;

        $existing = $this->dicomStudyRepository->isExistingDicomStudyForVisit($visitId);
        $notExisitingVisitId = Visit::factory()->create()->id;
        $notExisting = $this->dicomStudyRepository->isExistingDicomStudyForVisit($notExisitingVisitId);
        $this->assertTrue($existing);
        $this->assertFalse($notExisting);
    }

    public function testGetDicomsDataFromVisit()
    {
        $dicomSeries = DicomSeries::factory()->create();
        $visitId = $dicomSeries->dicomStudy->visit_id;

        $studyDetails = $this->dicomStudyRepository->getDicomsDataFromVisit($visitId, false, false);
        $this->assertEquals(1, sizeof($studyDetails));
        $this->assertEquals(1, sizeof($studyDetails[0]['dicom_series']));

        $dicomSeries->dicomStudy()->delete();
        $studyDetails = $this->dicomStudyRepository->getDicomsDataFromVisit($visitId, false, false);
        $this->assertEquals(0, sizeof($studyDetails));
    }

    public function testGetDicomsDataFromVisitIncludingDeleteStudies()
    {
        $dicomSeries = DicomSeries::factory()->create();
        $visitId = $dicomSeries->dicomStudy->visit_id;

        $dicomSeries->dicomStudy()->delete();
        $studyDetails = $this->dicomStudyRepository->getDicomsDataFromVisit($visitId, true, false);
        $this->assertEquals(1, sizeof($studyDetails[0]['dicom_series']));
    }

    public function testGetDicomsDataFromVisitIncludingDeleteSeries()
    {
        $dicomSeries = DicomSeries::factory()->create();
        $visitId = $dicomSeries->dicomStudy->visit_id;

        $dicomSeries->delete();
        $studyDetails = $this->dicomStudyRepository->getDicomsDataFromVisit($visitId, false, true);
        $this->assertEquals(1, sizeof($studyDetails[0]['dicom_series']));
    }

    public function testGetOrthancStudyByStudyInstanceUID()
    {

        $orthancStudies = DicomStudy::factory()->count(5)->create();
        $orthancStudies->get(3)->delete();

        $answer = $this->dicomStudyRepository->getDicomStudy($orthancStudies->get(4)->study_uid, false);
        $this->assertEquals($orthancStudies->get(4)->orthanc_id, $answer['orthanc_id']);

        $answer = $this->dicomStudyRepository->getDicomStudy($orthancStudies->get(3)->study_uid, true);
        $this->assertEquals($orthancStudies->get(3)->orthanc_id, $answer['orthanc_id']);

        $this->expectException(ModelNotFoundException::class);
        $answer = $this->dicomStudyRepository->getDicomStudy($orthancStudies->get(3)->study_uid, false);
    }

    public function testGetChildSeries()
    {

        $orthancStudies = DicomStudy::factory()->count(5)->create();

        $orthancSeries = DicomSeries::factory()
            ->count(3)
            ->for($orthancStudies->first())
            ->create();

        $orthancSeries->get(1)->delete();

        $series = $this->dicomStudyRepository->getChildSeries($orthancStudies->first()->study_uid, false);
        $this->assertEquals(2, sizeof($series));

        $series = $this->dicomStudyRepository->getChildSeries($orthancStudies->first()->study_uid, true);
        $this->assertEquals(1, sizeof($series));
    }

    public function testGetDicomStudyFromVisitIdArray()
    {

        $visit = Visit::factory()->count(2)->create();

        DicomStudy::factory()->count(5)->create();

        $dicomStudy1 = DicomStudy::factory()->visitId($visit->first()->id)->create();
        $dicomStudy2 = DicomStudy::factory()->visitId($visit->last()->id)->create();

        $answer = $this->dicomStudyRepository->getDicomStudyFromVisitIdArray([$visit->first()->id, $visit->last()->id], false);
        $this->assertEquals(2, sizeof($answer));

        $dicomStudy1->delete();
        $answer = $this->dicomStudyRepository->getDicomStudyFromVisitIdArray([$visit->first()->id, $visit->last()->id], false);
        $this->assertEquals(1, sizeof($answer));

        //Should Include the deleted one
        $answer = $this->dicomStudyRepository->getDicomStudyFromVisitIdArray([$visit->first()->id, $visit->last()->id], true);
        $this->assertEquals(2, sizeof($answer));
    }


    public function testGetDicomStudyFromVisitIdArrayWithSeries()
    {

        $visit = Visit::factory()->count(2)->create();

        DicomStudy::factory()->count(5)->create();

        $dicomStudy1 = DicomStudy::factory()->visitId($visit->first()->id)->create();
        $dicomStudy2 = DicomStudy::factory()->visitId($visit->last()->id)->create();

        $answer = $this->dicomStudyRepository->getDicomStudyFromVisitIdArrayWithSeries([$visit->first()->id, $visit->last()->id], false, false);
        $this->assertEquals(2, sizeof($answer));

        $dicomStudy1->delete();
        $answer = $this->dicomStudyRepository->getDicomStudyFromVisitIdArrayWithSeries([$visit->first()->id, $visit->last()->id], false, false);
        $this->assertEquals(1, sizeof($answer));

        //Should Include the deleted one
        $answer = $this->dicomStudyRepository->getDicomStudyFromVisitIdArrayWithSeries([$visit->first()->id, $visit->last()->id], true, true);
        $this->assertEquals(2, sizeof($answer));
    }

}
