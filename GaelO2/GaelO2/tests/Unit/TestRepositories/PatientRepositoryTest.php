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

        $patientEntity = new PatientEntity();

        $patientEntity->code = 123456789123456;
        $patientEntity->lastName = 'S';
        $patientEntity->firstName = 'K';
        $patientEntity->birthDay = 25;
        $patientEntity->birthMonth = 05;
        $patientEntity->birthYear = 1900;
        $patientEntity->gender = 'M';
        $patientEntity->registrationDate = '2020-01-01';
        $patientEntity->investigatorName = 'salim';
        $patientEntity->studyName = $studyName;
        $patientEntity->centerCode = 0;

        $this->patientRepository->addPatientInStudy($patientEntity, $studyName);

        $patientRecord = Patient::findOrFail($patientEntity->code);

        $this->assertEquals(1900, $patientRecord->birth_year);
    }

    public function testUpdatePatient()
    {

        $patient = Patient::factory()->create();

        $this->patientRepository->updatePatient(
            $patient->code,
            $patient->lastname,
            $patient->firstname,
            $patient->gender,
            $patient->birth_day,
            $patient->birth_month,
            $patient->birth_year,
            $patient->study_name,
            $patient->registration_date,
            'New Investigator',
            $patient->center_code
        );

        $updatedPatient = Patient::find($patient->code);
        $this->assertEquals('New Investigator', $updatedPatient->investigator_name);
        $this->assertEquals($patient->birth_year, $updatedPatient->birth_year);
    }

    public function testGetPatientWithCenterDetails()
    {
        $patient = Patient::factory()->create();
        $patientEntity = $this->patientRepository->getPatientWithCenterDetails($patient->code);

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
}
