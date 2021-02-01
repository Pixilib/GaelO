<?php

namespace Tests\Unit\TestRepositories;

use App\GaelO\Repositories\OrthancStudyRepository;
use App\Models\OrthancSeries;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

use App\Models\OrthancStudy;
use App\Models\Study;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class OrthancStudyRepositoryTest extends TestCase
{
    private OrthancStudyRepository $orthancStudyRepository;

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
        $this->orthancStudyRepository = new OrthancStudyRepository(new OrthancStudy());
    }


    public function testReactivateByStudyInstanceUid()
    {
        $orthancStudy = OrthancStudy::factory()->create();
        $orthancStudy->delete();
        $this->assertEquals(0, OrthancStudy::get()->count());
        $this->orthancStudyRepository->reactivateByStudyInstanceUID($orthancStudy->study_uid);
        $this->assertEquals(1, OrthancStudy::get()->count());
    }

    public function testAddStudy()
    {

        $this->orthancStudyRepository->addStudy(
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

        $orthancStudyEntity = OrthancStudy::find('6b9e19d9-62094390-5f9ddb01-4a191ae7-9766b715');
        $this->assertEquals(3, $orthancStudyEntity->number_of_series);
    }

    public function testModifyStudy()
    {
        $orthancStudy = OrthancStudy::factory()->create();
        $this->orthancStudyRepository->updateStudy(
            $orthancStudy->orthanc_id,
            Visit::factory()->create()->id,
            User::factory()->create()->id,
            '2020-01-01',
            null,
            null,
            '6b9e19d9-62094390-5f9ddb01-4a191ae7-9766b716',
            '12.659865.5',
            'newStudyDescription',
            '6b9e19d9-62094390-5f9ddb01-4a191ae7-9766b717',
            null,
            null,
            5,
            1500,
            500,
            1000
        );

        $orthancStudyEntity = OrthancStudy::find($orthancStudy->orthanc_id);
        $this->assertEquals(5, $orthancStudyEntity->number_of_series);
    }

    public function testIsExistingOriginalOrthancStudyId()
    {

        $orthancStudy = OrthancStudy::factory()->create();
        //2 study are created when factory of patient and visit type
        $studyName = $orthancStudy->visit->visitType->visitGroup->study->name;
        $studyName2 = Study::factory()->create();

        $answer  = $this->orthancStudyRepository->isExistingOriginalOrthancStudyID($orthancStudy->anon_from_orthanc_id, $studyName);
        $answer2  = $this->orthancStudyRepository->isExistingOriginalOrthancStudyID($orthancStudy->anon_from_orthanc_id, $studyName2);
        //One study should be true, the other false
        $this->assertTrue($answer);
        $this->assertFalse($answer2);

    }

    public function testIsExistingOrthancStudyId()
    {
        $orthancStudy = OrthancStudy::factory()->create();

        $existing = $this->orthancStudyRepository->isExistingOrthancStudyID($orthancStudy->orthanc_id);
        $this->assertTrue($existing);

        $orthancStudy->delete();
        $existing = $this->orthancStudyRepository->isExistingOrthancStudyID($orthancStudy->orthanc_id);
        $this->assertFalse($existing);
    }

    public function testGetStudyOrthancIdFromVisit()
    {
        $orthancStudy = OrthancStudy::factory()->create();
        $visitId = Visit::get()->first()->id;
        $studyOrthancId = $this->orthancStudyRepository->getStudyOrthancIDFromVisit($visitId);
        $this->assertEquals($orthancStudy->orthanc_id, $studyOrthancId);
    }

    public function testIsExisitingDicomStudyForVisit()
    {
        OrthancStudy::factory()->create();
        $visitId = Visit::get()->first()->id;

        $existing = $this->orthancStudyRepository->isExistingDicomStudyForVisit($visitId);
        $notExisitingVisitId = Visit::factory()->create()->id;
        $notExisting = $this->orthancStudyRepository->isExistingDicomStudyForVisit($notExisitingVisitId);
        $this->assertTrue($existing);
        $this->assertFalse($notExisting);
    }

    public function testGetDicomsDataFromVisit()
    {
        $orthancSeries = OrthancSeries::factory()->create();
        $visitId = Visit::get()->first()->id;
        $studyDetails = $this->orthancStudyRepository->getDicomsDataFromVisit($visitId, false);
        $this->assertEquals(1, sizeof($studyDetails[0]['series']));

        $orthancSeries->orthancStudy()->delete();
        $studyDetails = $this->orthancStudyRepository->getDicomsDataFromVisit($visitId, true);
        $this->assertEquals(1, sizeof($studyDetails[0]['series']));

        $studyDetails = $this->orthancStudyRepository->getDicomsDataFromVisit($visitId, false);
        $this->assertEmpty($studyDetails);
    }

    public function testGetOrthancStudyByStudyInstanceUID()
    {

        $orthancStudies = OrthancStudy::factory()->count(5)->create();
        $orthancStudies->get(3)->delete();

        $answer = $this->orthancStudyRepository->getOrthancStudyByStudyInstanceUID($orthancStudies->get(4)->study_uid, false);
        $this->assertEquals($orthancStudies->get(4)->orthanc_id, $answer['orthanc_id']);

        $answer = $this->orthancStudyRepository->getOrthancStudyByStudyInstanceUID($orthancStudies->get(3)->study_uid, true);
        $this->assertEquals($orthancStudies->get(3)->orthanc_id, $answer['orthanc_id']);

        $this->expectException(ModelNotFoundException::class);
        $answer = $this->orthancStudyRepository->getOrthancStudyByStudyInstanceUID($orthancStudies->get(3)->study_uid, false);
    }

    public function testGetChildSeries()
    {

        $orthancStudies = OrthancStudy::factory()->create();

        $orthancSeries = OrthancSeries::factory()
            ->count(3)
            ->for($orthancStudies)
            ->create();

        $orthancSeries->get(1)->delete();

        $series = $this->orthancStudyRepository->getChildSeries($orthancStudies->orthanc_id, false);
        $this->assertEquals(2, sizeof($series));

        $series = $this->orthancStudyRepository->getChildSeries($orthancStudies->orthanc_id, true);
        $this->assertEquals(1, sizeof($series));
    }
}
