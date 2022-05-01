<?php

namespace Tests\Unit\TestRepositories;

use App\GaelO\Repositories\PatientRepository;
use App\GaelO\Entities\PatientEntity;
use App\Models\Center;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

use App\Models\Patient;
use App\Models\Study;

class PatientRepositoryTest extends TestCase
{
    private PatientRepository $patientRepository;

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
        $this->patientRepository = new PatientRepository(new Patient());
    }

    public function testCreatePatient()
    {

        $studyName = Study::factory()->create()->name;

        $this->patientRepository->addPatientInStudy('123456789123456', '3', 'S', 'K', 'M', 25, 05, 1900, '2020-01-01', 'salim', 0 , $studyName);

        $patientRecord = Patient::findOrFail('123456789123456');

        $this->assertEquals(1900, $patientRecord->birth_year);
    }

    public function testUpdatePatient()
    {

        $patient = Patient::factory()->create();

        $this->patientRepository->updatePatient(
            $patient->id,
            $patient->lastname,
            $patient->firstname,
            $patient->gender,
            $patient->birth_day,
            $patient->birth_month,
            $patient->birth_year,
            $patient->study_name,
            $patient->registration_date,
            'New Investigator',
            $patient->center_code,
            $patient->inclusion_status,
            $patient->withdraw_reason,
            $patient->withdraw_date
        );

        $updatedPatient = Patient::find($patient->id);
        $this->assertEquals('New Investigator', $updatedPatient->investigator_name);
        $this->assertEquals($patient->birth_year, $updatedPatient->birth_year);
    }

    public function testGetPatientWithCenterDetails()
    {
        $patient = Patient::factory()->create();
        $patientEntity = $this->patientRepository->getPatientWithCenterDetails($patient->id);

        $this->assertArrayHasKey('center', $patientEntity);
        $this->assertArrayHasKey('inclusion_status', $patientEntity);
    }

    public function testGetPatientInStudy()
    {
        $study = Study::factory()->count(2)->create();

        Patient::factory()->studyName($study->first()->name)->count(6)->create();
        Patient::factory()->studyName($study->last()->name)->count(8)->create();

        $patients = $this->patientRepository->getPatientsInStudy($study->first()->name);
        $this->assertEquals(6, sizeof($patients));
    }

    public function testGetAllPatientNumberOfStudy()
    {

        Patient::factory()->count(30)->create();
        $study = Study::factory()->create();
        Patient::factory()->studyName($study->name)->count(5)->create();
        $patientIds = $this->patientRepository->getAllPatientsNumberInStudy($study->name);
        $this->assertEquals(5, sizeof($patientIds));
    }

    public function testGetPatientInStudyInCenters()
    {
        $study = Study::factory()->count(2)->create();
        $centers = Center::factory()->count(3)->create();

        Patient::factory()->studyName($study->first()->name)->centerCode($centers->get(0)->code)->count(8)->create();
        Patient::factory()->studyName($study->first()->name)->centerCode($centers->get(1)->code)->count(3)->create();

        Patient::factory()->studyName($study->last()->name)->centerCode($centers->get(0)->code)->count(2)->create();

        $selectedPatients = $this->patientRepository->getPatientsInStudyInCenters($study->first()->name, [$centers->get(0)->code, $centers->get(1)->code]);
        $this->assertEquals(11, sizeof($selectedPatients));
    }

    public function testGetPatientsFromIdArray() {
        $patient1 = Patient::factory()->create();
        $patient2 = Patient::factory()->create();
        $patientIdArray = [strval($patient1->id), strval($patient2->id)];
        $patientEntitiesArray = $this->patientRepository->getPatientsFromIdArray($patientIdArray);
        $fetchedPatientsCodes = array_column($patientEntitiesArray, 'id');
        $this->assertTrue(!array_diff($fetchedPatientsCodes, $patientIdArray));
    }
}