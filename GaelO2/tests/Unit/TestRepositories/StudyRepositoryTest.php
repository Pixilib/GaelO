<?php

namespace Tests\Unit\TestRepositories;

use App\GaelO\Repositories\StudyRepository;
use App\Models\DicomSeries;
use App\Models\DicomStudy;
use App\Models\Patient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

use App\Models\Study;
use App\Models\Visit;
use App\Models\VisitType;

class StudyRepositoryTest extends TestCase
{
    private StudyRepository $studyRepository;

    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed');
        $this->studyRepository = new StudyRepository(new Study());
    }

    public function testCreateStudy(){
        $this->studyRepository->addStudy('myStudy', '12345', 5,  'contact@gaelo.fr',true, false, false, null);
        $studyEntity  = Study::find('myStudy');

        $this->assertEquals('myStudy', $studyEntity->name);
        $this->assertEquals('12345', $studyEntity->code);
        $this->assertEquals( 5 , $studyEntity->patient_code_length);
        $this->assertEquals( 'contact@gaelo.fr' , $studyEntity->contact_email);
        $this->assertTrue( (bool) $studyEntity->controller_show_all);
        $this->assertFalse( (bool) $studyEntity->monitor_show_all);
        $this->assertFalse( (bool) $studyEntity->documentation_mandatory);
        $this->assertEquals( null , $studyEntity->ancillary_of);
    }

    public function testIsExistingStudy(){
        $studyEntity = Study::factory()->create();
        $answer  = $this->studyRepository->isExistingStudyName($studyEntity->name);
        $answer2  = $this->studyRepository->isExistingStudyName('NotExistingStudyName');
        $this->assertTrue($answer);
        $this->assertFalse($answer2);
    }

    public function testGetStudies(){

        Study::factory()->create();
        Study::factory()->create()->delete();

        $answer = $this->studyRepository->getStudies();
        $answer2 = $this->studyRepository->getStudies(true);

        $this->assertEquals(1, sizeof($answer) );
        $this->assertEquals(2, sizeof($answer2) );

    }

    public function testGetStudyWithDetails(){

        $visitType = VisitType::factory()->create();

        $studyName = $visitType->visitGroup->study_name;

        $answer = $this->studyRepository->getstudyWithDetails($studyName);

        $this->assertArrayHasKey('visit_groups', $answer);
        $this->assertArrayHasKey('visit_types', $answer['visit_groups'][0]);

    }

    public function testGetAllStudiesWithDetails(){

        VisitType::factory()->count(5)->create();
        VisitType::factory()->create()->delete();

        $answer = $this->studyRepository->getAllStudiesWithDetails();

        $this->assertEquals(6, sizeof($answer));
        $this->assertArrayHasKey('visit_groups', $answer[0]);
        $this->assertArrayHasKey('visit_types', $answer[0]['visit_groups'][0]);

    }

    public function testReactivateStudy(){

        $study = Study::factory()->create();
        $study->delete();

        $this->studyRepository->reactivateStudy($study->name);

        $updatedStudy = Study::find($study->name);
        $this->assertNull($updatedStudy['deleted_at']);
    }

    public function testGetAncilariesStudies(){
        $study = Study::factory()->create();

        Study::factory()->ancillaryOf($study->name)->count(5)->create();
        $ancilarriesStudies = $this->studyRepository->getAncillariesStudyOfStudy($study->name);
        $this->assertEquals(5, sizeof($ancilarriesStudies));
    }

    public function testGetStatistics(){
        $study = Study::factory()->create();
        $patients = Patient::factory()->count(30)->studyName($study->name)->create();
        $visit = Visit::factory()->patientId($patients->first()->id)->create();
        $dicomStudy = DicomStudy::factory()->visitId($visit->id)->create();
        DicomSeries::factory()->studyInstanceUID($dicomStudy->study_uid)->count(5)->create();

        $studyName = $study->name;

        $study = Study::findOrFail($studyName);
        $statistics = $this->studyRepository->getStudyStatistics($study->name);

        $this->assertEquals($statistics['patients_count'], 30);
        $this->assertEquals($statistics['dicom_studies_count'], 1);
        $this->assertEquals($statistics['dicom_series_count'], 5);
        $this->assertGreaterThan(0 , $statistics['dicom_instances_count']);
        $this->assertGreaterThan(0 , $statistics['dicom_disk_size']);


    }


}
