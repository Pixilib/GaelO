<?php

namespace Tests\Feature;

use App\GaelO\Constants\Constants;
use App\OrthancSeries;
use App\OrthancStudy;
use App\Patient;
use App\ReviewStatus;
use App\Study;
use App\User;
use App\Visit;
use App\VisitGroup;
use App\VisitType;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Artisan;
use Laravel\Passport\Passport;
use Tests\AuthorizationTools;
use Tests\TestCase;

class DicomDeleteSeriesTest extends TestCase
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

        Artisan::call('passport:install');
        Passport::actingAs(
            User::where('id', 1)->first()
        );

        $this->study = factory(Study::class)->create(['patient_code_prefix' => 1234]);
        $this->visitGroup = factory(VisitGroup::class)->create(['study_name' => $this->study->name]);
        $this->visitType = factory(VisitType::class)->create(['visit_group_id' => $this->visitGroup['id']]);
        $this->patient = factory(Patient::class)->create(['code' => 12341234123412, 'study_name' => $this->study->name, 'center_code' => 0]);
        $this->visit = factory(Visit::class)->create([
            'creator_user_id' => 1,
            'patient_code' => $this->patient['code'],
            'visit_type_id' => $this->visitType['id'],
            'status_done' => 'Done',
            'state_quality_control'=>Constants::QUALITY_CONTROL_NOT_DONE
        ]);
        $this->reviewStatus = factory(ReviewStatus::class)->create([
            'visit_id' => $this->visit->id,
            'study_name' => $this->study->name,
        ]);

        $this->orthancStudy = factory(OrthancStudy::class)->create([
            'visit_id' => $this->visit->id,
            'study_uid' => '1.2.826.0.1.3680043.5.2014.6.4.10.27.08.20160309120853.168.11008',
            'uploader_id' => 1
        ]);

        $this->orthancSeries = factory(OrthancSeries::class)->create([
            'series_uid' => '1.2.826.0.1.3680043.5.2014.6.4.10.27.08.20160309120853.168.11009',
            'orthanc_study_id' => $this->orthancStudy->orthanc_id
        ]);

        $this->orthancSeries2 = factory(OrthancSeries::class)->create([
            'series_uid' => '1.2.826.0.1.3680043.5.2014.6.4.10.27.08.20160309120853.168.11010',
            'orthanc_study_id' => $this->orthancStudy->orthanc_id
        ]);
    }

    public function testDeleteSeries()
    {
        AuthorizationTools::addRoleToUser(1, Constants::ROLE_SUPERVISOR, $this->study->name);
        $payload = ['reason' => 'wrong series'];
        $response = $this->delete('api/dicom-series/' . $this->orthancSeries->series_uid . '?role=Supervisor', $payload);
        $response->assertStatus(200);
    }

    public function testDeleteLastSeries(){

        AuthorizationTools::addRoleToUser(1, Constants::ROLE_SUPERVISOR, $this->study->name);
        $payload = ['reason' => 'wrong series'];
        $this->delete('api/dicom-series/' . $this->orthancSeries->series_uid . '?role=Supervisor', $payload);
        $this->delete('api/dicom-series/' . $this->orthancSeries2->series_uid . '?role=Supervisor', $payload);
        $orthancStudyEntity = OrthancStudy::withTrashed()->find($this->orthancStudy->orthanc_id);
        $visitEntity = Visit::find($this->visit->id);

        //Expect study to be deleted
        $this->assertNotNull($orthancStudyEntity['deleted_at']);
        $this->assertEquals(Constants::INVESTIGATOR_FORM_DRAFT, $visitEntity['state_investigator_form']);
        $this->assertEquals(Constants::UPLOAD_STATUS_NOT_DONE, $visitEntity['upload_status']);

    }

    public function testDeleteSeriesShouldFailNoRole()
    {
        $payload = ['reason' => 'wrong series'];
        $response = $this->delete('api/dicom-series/' . $this->orthancSeries->series_uid . '?role=Supervisor', $payload);
        $response->assertStatus(403);
    }

    public function testDeleteSeriesShouldFailNoReason()
    {
        $payload = [];
        $response = $this->delete('api/dicom-series/' . $this->orthancSeries->series_uid . '?role=Supervisor', $payload);
        $response->assertStatus(400);
    }

    public function testDeleteSeriesShouldFailQcDone()
    {
        $this->visit->state_quality_control = Constants::QUALITY_CONTROL_ACCEPTED;
        $this->visit->save();
        $payload = ['reason' => 'wrong series'];
        $response = $this->delete('api/dicom-series/' . $this->orthancSeries->series_uid . '?role=Supervisor', $payload);
        $response->assertStatus(403);
    }
}
