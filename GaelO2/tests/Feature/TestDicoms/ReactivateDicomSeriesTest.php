<?php

namespace Tests\Feature\TestDicoms;

use App\GaelO\Constants\Constants;
use App\GaelO\Constants\Enums\InvestigatorFormStateEnum;
use App\GaelO\Constants\Enums\QualityControlStateEnum;
use App\GaelO\Repositories\TrackerRepository;
use App\Models\DicomSeries;
use App\Models\Review;
use App\Models\ReviewStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Mockery\MockInterface;
use Tests\AuthorizationTools;
use Tests\TestCase;

class ReactivateDicomSeriesTest extends TestCase
{

    use RefreshDatabase;
    private MockInterface $trackerSpy;
    private string $studyName;
    private DicomSeries $dicomSeries;
    private Review $investigatorForm;

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

        $this->trackerSpy = $this->spy(TrackerRepository::class);
        app()->instance(TrackerRepository::class, $this->trackerSpy);
    }

    public function testReactivateSeriesInvestigator()
    {
        $currentUserId = AuthorizationTools::actAsAdmin(false);
        $patientCenterCode = $this->dicomSeries->dicomStudy->visit->patient->center_code;
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_INVESTIGATOR, $this->studyName);
        AuthorizationTools::addAffiliatedCenter($currentUserId, $patientCenterCode);

        $this->dicomSeries->delete();
        $response = $this->post('api/dicom-series/' . $this->dicomSeries->series_uid.'/activate?role=Investigator&studyName='.$this->studyName, ['reason' => 'good series']);
        $response->assertStatus(200);
        $this->trackerSpy->shouldHaveReceived('writeAction')->once()->with($currentUserId, Constants::ROLE_INVESTIGATOR, Mockery::any(), Mockery::any(), Constants::TRACKER_REACTIVATE_DICOM_SERIES, Mockery::any());
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
        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_SUPERVISOR, $this->studyName);

        $this->dicomSeries->delete();
        $response = $this->post('api/dicom-series/' . $this->dicomSeries->series_uid.'/activate?role=Supervisor&studyName='.$this->studyName, ['reason' => 'good series']);
        $response->assertStatus(200);
        $this->trackerSpy->shouldHaveReceived('writeAction')->once()->with($currentUserId, Constants::ROLE_SUPERVISOR, Mockery::any(), Mockery::any(), Constants::TRACKER_REACTIVATE_DICOM_SERIES, Mockery::any());
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

        $this->dicomSeries->delete();
        $response = $this->post('api/dicom-series/' . $this->dicomSeries->series_uid.'/activate?role=Supervisor&studyName='.$this->studyName, ['reason' => 'good series']);

        $response->assertStatus(200);

    }

    public function testReactivateSeriesForbiddenIfInvestigatorAndQcNotNeeded()
    {

        $this->dicomSeries->dicomStudy->visit->state_quality_control = QualityControlStateEnum::NOT_NEEDED->value;
        $this->dicomSeries->dicomStudy->visit->save();

        $userId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($userId, Constants::ROLE_INVESTIGATOR, $this->studyName);

        $this->dicomSeries->delete();
        $response = $this->post('api/dicom-series/' . $this->dicomSeries->series_uid.'/activate?role=Supervisor&studyName='.$this->studyName, ['reason' => 'good series']);

        $response->assertStatus(403);

    }
}
