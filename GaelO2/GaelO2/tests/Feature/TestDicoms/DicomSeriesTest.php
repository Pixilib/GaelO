<?php

namespace Tests\Feature\TestDicoms;

use App\GaelO\Constants\Constants;
use App\Models\OrthancSeries;
use App\Models\OrthancStudy;
use App\Models\Visit;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\AuthorizationTools;
use Tests\TestCase;

class DicomSeriesTest extends TestCase
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
        $this->orthancSeries = OrthancSeries::factory()->create();
        $this->studyName = $this->orthancSeries->orthancStudy->visit->visitType->visitGroup->study->name;

        //Set visit QC at Not Done
        $this->orthancSeries->orthancStudy->visit->state_quality_control = Constants::QUALITY_CONTROL_NOT_DONE;
        $this->orthancSeries->orthancStudy->visit->save();
    }

    public function testDeleteSeries()
    {
        $userId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($userId, Constants::ROLE_SUPERVISOR, $this->studyName);
        $payload = ['reason' => 'wrong series'];
        $response = $this->delete('api/dicom-series/' . $this->orthancSeries->series_uid . '?role=Supervisor', $payload);
        $response->assertStatus(200);
    }

    public function testDeleteLastSeries()
    {

        $userId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($userId, Constants::ROLE_SUPERVISOR, $this->studyName);

        $payload = ['reason' => 'wrong series'];
        $this->delete('api/dicom-series/' . $this->orthancSeries->series_uid . '?role=Supervisor', $payload)->assertStatus(200);
        $orthancStudyEntity = OrthancStudy::withTrashed()->find($this->orthancSeries->orthancStudy->orthanc_id);
        $visitEntity = Visit::find($this->orthancSeries->orthancStudy->visit->id);

        //Expect study to be deleted
        $this->assertNotNull($orthancStudyEntity['deleted_at']);
        $this->assertEquals(Constants::INVESTIGATOR_FORM_DRAFT, $visitEntity['state_investigator_form']);
        $this->assertEquals(Constants::UPLOAD_STATUS_NOT_DONE, $visitEntity['upload_status']);
    }

    public function testDeleteSeriesShouldFailNoRole()
    {
        AuthorizationTools::actAsAdmin(false);

        $payload = ['reason' => 'wrong series'];
        $response = $this->delete('api/dicom-series/' . $this->orthancSeries->series_uid . '?role=Supervisor', $payload);
        $response->assertStatus(403);
    }

    public function testDeleteSeriesShouldFailNoReason()
    {
        $userId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($userId, Constants::ROLE_SUPERVISOR, $this->studyName);

        $payload = [];
        $response = $this->delete('api/dicom-series/' . $this->orthancSeries->series_uid . '?role=Supervisor', $payload);
        $response->assertStatus(400);
    }

    public function testDeleteSeriesShouldFailQcDone()
    {
        $userId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($userId, Constants::ROLE_SUPERVISOR, $this->studyName);

        //Set visit QC at Accepted
        $this->orthancSeries->orthancStudy->visit->state_quality_control = Constants::QUALITY_CONTROL_ACCEPTED;
        $this->orthancSeries->orthancStudy->visit->save();

        $payload = ['reason' => 'wrong series'];
        $response = $this->delete('api/dicom-series/' . $this->orthancSeries->series_uid . '?role=Supervisor', $payload);
        $response->assertStatus(403);
    }

    public function testReactivateSeries()
    {
        $userId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($userId, Constants::ROLE_SUPERVISOR, $this->studyName);

        $this->orthancSeries->delete();
        $response = $this->patch('api/dicom-series/' . $this->orthancSeries->series_uid, []);
        $response->assertStatus(200);
    }

    public function testReactivateSeriesFailNotSupervisor()
    {
        AuthorizationTools::actAsAdmin(false);

        $this->orthancSeries->delete();
        $response = $this->patch('api/dicom-series/' . $this->orthancSeries->series_uid, []);
        $response->assertStatus(403);
    }

    public function testReactivateSeriesFailParentStudyDeleted()
    {
        $userId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($userId, Constants::ROLE_SUPERVISOR, $this->studyName);

        $this->orthancSeries->orthancStudy->delete();
        $response = $this->patch('api/dicom-series/' . $this->orthancSeries->series_uid, []);
        $response->assertStatus(400);
    }

    public function testReactivateStudy()
    {

        $userId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($userId, Constants::ROLE_SUPERVISOR, $this->studyName);

        $this->orthancSeries->orthancStudy->delete();
        $response = $this->patch('api/dicom-study/' . $this->orthancSeries->orthancStudy->study_uid, []);
        $response->assertStatus(200);
    }


    public function testReactivateStudyShouldFailNoRole()
    {

        AuthorizationTools::actAsAdmin(false);

        $this->orthancSeries->orthancStudy->delete();
        $response = $this->patch('api/dicom-study/' . $this->orthancSeries->orthancStudy->study_uid, []);
        $response->assertStatus(403);
    }


    public function testReactivateStudyShouldFailExistingAlreadyActivatedStudy()
    {
        //SK ICI SUREMENT LE TEST DOIT ETRE FAIT DANS LE SERVICE
        $userId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($userId, Constants::ROLE_SUPERVISOR, $this->studyName);

        $response = $this->patch('api/dicom-study/' . $this->orthancSeries->orthancStudy->study_uid, []);
        $response->assertStatus(400);
    }
}
