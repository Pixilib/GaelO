<?php

namespace Tests\Feature\TestDeleteCommand;

use App\GaelO\Services\OrthancService;
use App\Models\DicomSeries;
use App\Models\DicomStudy;
use App\Models\Patient;
use App\Models\Review;
use App\Models\ReviewStatus;
use App\Models\Study;
use App\Models\Visit;
use App\Models\VisitGroup;
use App\Models\VisitType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Tests\TestCase;

class DeleteCommandTest extends TestCase
{

    use RefreshDatabase;
    private Study $study;
    private VisitGroup $visitGroup;
    private VisitType $visitType;
    private Patient $patient;
    private Visit $visit;
    private DicomStudy $dicomStudy;
    private DicomSeries $dicomSeries;
    private Review $review;
    private ReviewStatus $reviewStatus;

    protected function setUp(): void
    {
        $this->markTestSkipped();
        parent::setUp();
        $this->artisan('db:seed');
        $this->study = Study::factory()->create();
        $this->visitGroup = VisitGroup::factory()->studyName($this->study->name)->create();
        $this->visitType = VisitType::factory()->visitGroupId($this->visitGroup->id)->create();
        $this->patient = Patient::factory()->studyName($this->study->name)->create();
        $this->visit = Visit::factory()->patientId($this->patient->id)->visitTypeId($this->visitType->id)->create();
        $this->dicomStudy = DicomStudy::factory()->visitId($this->visit->id)->create();
        $this->dicomSeries = DicomSeries::factory()->studyInstanceUid($this->dicomStudy->study_uid)->create();
        $this->review = Review::factory()->visitId($this->visit->id)->studyName($this->study->name)->create();
        $this->reviewStatus = ReviewStatus::factory()->visitId($this->visit->id)->studyName($this->study->name)->create();

        $mockOrthancService = $this->partialMock(OrthancService::class, function (MockInterface $mock) {
            $mock->shouldReceive('setOrthancServer')->andReturn(null);
            $mock->shouldReceive('deleteFromOrthanc')->andReturn(true);
        });
        app()->instance(OrthancService::class, $mockOrthancService);
        $this->study->delete();
    }

    public function testDeleteCommandShouldFailWrongStudyNameConfirmation()
    {
        $studyName = $this->study->name;
        $this->artisan('gaelo:delete-study ' . $studyName)->expectsQuestion('Warning : Please confirm study Name', 'WrongStudyName')
            ->expectsOutput('Wrong study name, terminating');
    }

    public function testDeleteCommandShouldFailStudyNotDeleted()
    {
        $this->study->restore();
        $studyName = $this->study->name;
        $this->artisan('gaelo:delete-study ' . $studyName)->expectsQuestion('Warning : Please confirm study Name', $studyName)
            ->expectsOutput('Study is not soft deleted, terminating');
    }

    public function testDeleteCommand()
    {
        $studyName = $this->study->name;
        $this->artisan('gaelo:delete-study ' . $studyName)->expectsQuestion('Warning : Please confirm study Name', $studyName)
            ->expectsQuestion('Warning : This CANNOT be undone, do you wish to continue?', "\r\n")
            ->expectsTable(['orthanc_id'], [[$this->dicomSeries->orthanc_id]])
            ->expectsOutput('The command was successful !');
    }

    public function testDeleteCommandShouldFailBecauseExistingAncillary()
    {
        $studyName = $this->study->name;
        Study::factory()->ancillaryOf($studyName)->create();
        $this->artisan('gaelo:delete-study ' . $studyName)->expectsQuestion('Warning : Please confirm study Name', $studyName)
            ->expectsOutput('Delete all ancilaries studies first');
    }
}
