<?php

namespace Tests\Feature\TestDicoms;

use App\GaelO\Constants\Constants;
use App\Models\DicomSeries;
use App\Models\Review;
use App\Models\ReviewStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\AuthorizationTools;
use Tests\TestCase;

class ReactivateDicomStudyTest extends TestCase
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
        $visit->state_investigator_form = Constants::INVESTIGATOR_FORM_DONE;
        $visit->save();

        //Set visit QC at Not Done
        $this->dicomSeries->dicomStudy->visit->state_quality_control = Constants::QUALITY_CONTROL_NOT_DONE;
        $this->dicomSeries->dicomStudy->visit->save();
    }

    public function testReactivateStudy()
    {
        $userId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($userId, Constants::ROLE_SUPERVISOR, $this->studyName);

        $this->dicomSeries->dicomStudy->delete();
        //At study deletion the investigator form is Draft or Not Done
        $this->dicomSeries->dicomStudy->visit->state_investigator_form = Constants::INVESTIGATOR_FORM_DRAFT;
        $this->dicomSeries->dicomStudy->visit->save();

        $response = $this->post('api/dicom-study/' . $this->dicomSeries->dicomStudy->study_uid.'/activate?studyName='.$this->studyName, ['reason' => 'correct study']);
        $response->assertStatus(200);
    }

    public function testReactivateStudyShouldFailNotSameStudyName()
    {
        $userId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($userId, Constants::ROLE_SUPERVISOR, $this->studyName);

        $this->dicomSeries->dicomStudy->delete();
        //At study deletion the investigator form is Draft or Not Done
        $this->dicomSeries->dicomStudy->visit->state_investigator_form = Constants::INVESTIGATOR_FORM_DRAFT;
        $this->dicomSeries->dicomStudy->visit->save();

        $response = $this->post('api/dicom-study/' . $this->dicomSeries->dicomStudy->study_uid.'/activate?studyName='.$this->studyName. 'error', ['reason' => 'correct study']);
        $response->assertStatus(403);
    }


    public function testReactivateStudyShouldFailNoReason()
    {

        $userId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($userId, Constants::ROLE_SUPERVISOR, $this->studyName);

        $this->dicomSeries->dicomStudy->delete();
        //At study deletion the investigator form is Draft or Not Done
        $this->dicomSeries->dicomStudy->visit->state_investigator_form = Constants::INVESTIGATOR_FORM_DRAFT;
        $this->dicomSeries->dicomStudy->visit->save();

        $response = $this->post('api/dicom-study/' . $this->dicomSeries->dicomStudy->study_uid.'/activate?studyName='.$this->studyName);
        $response->assertStatus(400);
    }


    public function testReactivateStudyShouldFailNoRole()
    {

        AuthorizationTools::actAsAdmin(false);

        $this->dicomSeries->dicomStudy->delete();
        $response = $this->post('api/dicom-study/' . $this->dicomSeries->dicomStudy->study_uid.'/activate?studyName='.$this->studyName, ['reason' => 'correct study']);
        $response->assertStatus(403);
    }


    public function testReactivateStudyShouldFailExistingAlreadyActivatedStudy()
    {
        $userId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($userId, Constants::ROLE_SUPERVISOR, $this->studyName);
        $response = $this->post('api/dicom-study/' . $this->dicomSeries->dicomStudy->study_uid.'/activate?studyName='.$this->studyName, ['reason' => 'correct study']);
        $response->assertStatus(400);
    }
}
