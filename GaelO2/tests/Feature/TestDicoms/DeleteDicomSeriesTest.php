<?php

namespace Tests\Feature\TestDicoms;

use App\GaelO\Constants\Constants;
use App\Models\DicomSeries;
use App\Models\DicomStudy;
use App\Models\Review;
use App\Models\ReviewStatus;
use App\Models\Visit;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\AuthorizationTools;
use Tests\TestCase;

class DeleteDicomSeriesTest extends TestCase
{

    use DatabaseMigrations {
        runDatabaseMigrations as baseRunDatabaseMigrations;
    }

    /**
     * Define hooks to migrate the database before and after each test.
     *
     * @return void
     */
    public function runDatabaseMigrations()
    {
        $this->baseRunDatabaseMigrations();
        $this->artisan('db:seed');
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->dicomSeries = DicomSeries::factory()->create();
        $this->studyName = $this->dicomSeries->dicomStudy->visit->patient->study_name;
        $visit = $this->dicomSeries->dicomStudy->visit;

        ReviewStatus::factory()->studyName($visit->patient->study_name)->visitId($visit->id)->create();

        //Fill investigator Form
        $this->investigatorForm = Review::factory()->studyName($this->studyName)->visitId($visit->id)->validated()->create();
        $visit->state_investigator_form = Constants::INVESTIGATOR_FORM_DONE;
        $visit->save();

        //Set visit QC at Not Done
        $this->dicomSeries->dicomStudy->visit->state_quality_control = Constants::QUALITY_CONTROL_NOT_DONE;
        $this->dicomSeries->dicomStudy->visit->save();
    }

    public function testDeleteSeries()
    {
        $userId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($userId, Constants::ROLE_SUPERVISOR, $this->studyName);
        $payload = ['reason' => 'wrong series'];
        $response = $this->delete('api/dicom-series/' . $this->dicomSeries->series_uid . '?role=Supervisor&studyName=' . $this->studyName, $payload);
        $response->assertStatus(200);
    }

    public function testDeleteLastSeries()
    {

        $userId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($userId, Constants::ROLE_SUPERVISOR, $this->studyName);

        $payload = ['reason' => 'wrong series'];
        $this->delete('api/dicom-series/' . $this->dicomSeries->series_uid . '?role=Supervisor&studyName=' . $this->studyName, $payload)->assertStatus(200);
        $dicomStudyEntity = DicomStudy::withTrashed()->find($this->dicomSeries->dicomStudy->study_uid);
        $visitEntity = Visit::find($this->dicomSeries->dicomStudy->visit->id);

        //Expect study to be deleted
        $this->assertNotNull($dicomStudyEntity['deleted_at']);
        $this->assertEquals(Constants::INVESTIGATOR_FORM_DRAFT, $visitEntity['state_investigator_form']);
        $this->assertEquals(Constants::UPLOAD_STATUS_NOT_DONE, $visitEntity['upload_status']);

        //Check Investigator form has been unlocked
        $localForm = Review::where('study_name', $this->studyName)->where('visit_id', $this->dicomSeries->dicomStudy->visit->id)->where('local', true)->sole();
        $this->assertFalse(boolval($localForm['validated']));
    }

    public function testDeleteSeriesShouldFailNotSameStudyName()
    {
        $userId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($userId, Constants::ROLE_SUPERVISOR, $this->studyName);
        $payload = ['reason' => 'wrong series'];
        $response = $this->delete('api/dicom-series/' . $this->dicomSeries->series_uid . '?role=Supervisor&studyName=' . $this->studyName .'wrong', $payload);
        $response->assertStatus(403);
    }

    public function testDeleteLastSeriesShouldFailController()
    {

        $userId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($userId, Constants::ROLE_CONTROLLER, $this->studyName);

        $payload = ['reason' => 'wrong series'];
        $this->delete('api/dicom-series/' . $this->dicomSeries->series_uid . '?role=Controller&studyName=' . $this->studyName, $payload)->assertStatus(403);
    }

    public function testDeleteSeriesShouldFailNoRole()
    {
        AuthorizationTools::actAsAdmin(false);

        $payload = ['reason' => 'wrong series'];
        $response = $this->delete('api/dicom-series/' . $this->dicomSeries->series_uid . '?role=Supervisor&studyName=' . $this->studyName, $payload);
        $response->assertStatus(403);
    }

    public function testDeleteSeriesShouldFailNoReason()
    {
        $userId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($userId, Constants::ROLE_SUPERVISOR, $this->studyName);

        $payload = [];
        $response = $this->delete('api/dicom-series/' . $this->dicomSeries->series_uid . '?role=Supervisor&studyName=' . $this->studyName, $payload);
        $response->assertStatus(400);
    }

    public function testDeleteSeriesShouldFailQcDone()
    {
        $userId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($userId, Constants::ROLE_SUPERVISOR, $this->studyName);

        //Set visit QC at Accepted
        $this->dicomSeries->dicomStudy->visit->state_quality_control = Constants::QUALITY_CONTROL_ACCEPTED;
        $this->dicomSeries->dicomStudy->visit->save();

        $payload = ['reason' => 'wrong series'];
        $response = $this->delete('api/dicom-series/' . $this->dicomSeries->series_uid . '?role=Supervisor&studyName=' . $this->studyName, $payload);
        $response->assertStatus(403);
    }
}
