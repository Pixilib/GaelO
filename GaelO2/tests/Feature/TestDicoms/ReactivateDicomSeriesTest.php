<?php

namespace Tests\Feature\TestDicoms;

use App\GaelO\Constants\Constants;
use App\GaelO\Constants\Enums\InvestigatorFormStateEnum;
use App\GaelO\Constants\Enums\QualityControlStateEnum;
use App\Models\DicomSeries;
use App\Models\Review;
use App\Models\ReviewStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\AuthorizationTools;
use Tests\TestCase;

class ReactivateDicomSeriesTest extends TestCase
{

    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed');
        $this->dicomSeries = DicomSeries::factory()->create();
        $this->studyName = $this->dicomSeries->dicomStudy->visit->patient->study_name;
        $visit = $this->dicomSeries->dicomStudy->visit;

        ReviewStatus::factory()->studyName($visit->patient->study_name)->visitId($visit->id)->create();

        //Fill investigator Form
        $this->investigatorForm = Review::factory()->studyName($this->studyName)->visitId($visit->id)->validated()->create();
        $visit->state_investigator_form = InvestigatorFormStateEnum::DONE->value;
        $visit->save();

        //Set visit QC at Not Done
        $this->dicomSeries->dicomStudy->visit->state_quality_control = QualityControlStateEnum::NOT_DONE->value;
        $this->dicomSeries->dicomStudy->visit->save();
    }

    public function testReactivateSeriesInvestigator()
    {
        $userId = AuthorizationTools::actAsAdmin(false);
        $patientCenterCode = $this->dicomSeries->dicomStudy->visit->patient->center_code;
        AuthorizationTools::addRoleToUser($userId, Constants::ROLE_INVESTIGATOR, $this->studyName);
        AuthorizationTools::addAffiliatedCenter($userId, $patientCenterCode);

        $this->dicomSeries->delete();
        $response = $this->post('api/dicom-series/' . $this->dicomSeries->series_uid.'/activate?role=Investigator&studyName='.$this->studyName, ['reason' => 'good series']);
        $response->assertStatus(200);
    }

    public function testReactivateSeriesInvestigatorShouldFailNotSameStudyName()
    {
        $userId = AuthorizationTools::actAsAdmin(false);
        $patientCenterCode = $this->dicomSeries->dicomStudy->visit->patient->center_code;
        AuthorizationTools::addRoleToUser($userId, Constants::ROLE_INVESTIGATOR, $this->studyName);
        AuthorizationTools::addAffiliatedCenter($userId, $patientCenterCode);

        $this->dicomSeries->delete();
        $response = $this->post('api/dicom-series/' . $this->dicomSeries->series_uid.'/activate?role=Investigator&studyName='.$this->studyName . 'error', ['reason' => 'good series']);
        $response->assertStatus(403);
    }


    public function testReactivateSeriesSupervisor()
    {
        $userId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($userId, Constants::ROLE_SUPERVISOR, $this->studyName);

        $this->dicomSeries->delete();
        $response = $this->post('api/dicom-series/' . $this->dicomSeries->series_uid.'/activate?role=Supervisor&studyName='.$this->studyName, ['reason' => 'good series']);
        $response->assertStatus(200);
    }

    public function testReactivateSeriesFailNotSupervisor()
    {
        AuthorizationTools::actAsAdmin(false);

        $this->dicomSeries->delete();
        $response = $this->post('api/dicom-series/' . $this->dicomSeries->series_uid.'/activate?role=Supervisor&studyName='.$this->studyName, ['reason' => 'good series']);
        $response->assertStatus(403);
    }

    public function testReactivateSeriesFailParentStudyDeleted()
    {
        $userId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($userId, Constants::ROLE_SUPERVISOR, $this->studyName);

        $this->dicomSeries->dicomStudy->delete();
        $response = $this->post('api/dicom-series/' . $this->dicomSeries->series_uid.'/activate?role=Supervisor&studyName='.$this->studyName, []);

        $response->assertStatus(400);
    }

    public function testReactivateSeriesAllowedIfSupervisorAndQcNotNeeded()
    {

        $this->dicomSeries->dicomStudy->visit->state_quality_control = QualityControlStateEnum::NOT_NEEDED->value;
        $this->dicomSeries->dicomStudy->visit->save();

        $userId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($userId, Constants::ROLE_SUPERVISOR, $this->studyName);

        $this->dicomSeries->dicomStudy->delete();
        $response = $this->post('api/dicom-series/' . $this->dicomSeries->series_uid.'/activate?role=Supervisor&studyName='.$this->studyName, []);

        $response->assertStatus(200);

    }

    public function testReactivateSeriesForbiddenIfInvestigatorAndQcNotNeeded()
    {

        $this->dicomSeries->dicomStudy->visit->state_quality_control = QualityControlStateEnum::NOT_NEEDED->value;
        $this->dicomSeries->dicomStudy->visit->save();

        $userId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($userId, Constants::ROLE_INVESTIGATOR, $this->studyName);

        $this->dicomSeries->dicomStudy->delete();
        $response = $this->post('api/dicom-series/' . $this->dicomSeries->series_uid.'/activate?role=Supervisor&studyName='.$this->studyName, []);

        $response->assertStatus(200);

    }
}
