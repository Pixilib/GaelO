<?php

namespace Tests\Unit\TestRepositories;

use App\GaelO\Constants\Constants;
use App\GaelO\Repositories\VisitRepository;
use App\Models\Patient;
use App\Models\Review;
use App\Models\ReviewStatus;
use App\Models\Study;
use App\Models\User;
use Tests\TestCase;
use App\Models\Visit;
use App\Models\VisitGroup;
use App\Models\VisitType;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;

class VisitRepositoryAncillaryTest extends TestCase
{
    private VisitRepository $visitRepository;

    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed');
        $this->visitRepository = new VisitRepository();
    }

    private function populateVisits()
    {
        $study = Study::factory()->create();
        //Create 2 patient in which we will populate of visits
        Patient::factory()->studyName($study->name)->count(5)->create();
        $patient = Patient::factory()->studyName($study->name)->create();
        $patient2 = Patient::factory()->studyName($study->name)->create();

        //Create visitGroup
        $visitGroupsCT = VisitGroup::factory()->studyName($study->name)->modality('CT')->create();
        $visitGroupsPT = VisitGroup::factory()->studyName($study->name)->modality('PT')->create();

        $visitGroups = collect([$visitGroupsCT, $visitGroupsPT]);

        //Create VisitType and Visits
        $visitGroups->each(function ($item, $key) use ($patient, $patient2) {
            $visitTypes = VisitType::factory()->visitGroupId($item->id)->count(3)->create();
            $visitTypes->each(function ($item, $key) use ($patient, $patient2) {
                $visit = Visit::factory()->visitTypeId($item->id)->patientId($patient->id)->create();
                $visit2 = Visit::factory()->visitTypeId($item->id)->uploadDone()->stateInvestigatorForm(Constants::INVESTIGATOR_FORM_DONE)->stateQualityControl(Constants::QUALITY_CONTROL_NOT_NEEDED)->patientId($patient2->id)->create();
            });
        });

        return [$patient, $patient2];
    }

    public function testGetPatientVisitsWithDefaultReviewStatus()
    {
        $patient = $this->populateVisits()[0];

        $visits = $this->visitRepository->getAllPatientsVisitsWithReviewStatus($patient->id, 'ancillary', false);
        $this->assertArrayHasKey('review_status', $visits[0]['review_status']);
        $this->assertArrayHasKey('review_available', $visits[0]['review_status']);
        $this->assertArrayHasKey('review_conclusion_value', $visits[0]['review_status']);
        $this->assertArrayHasKey('review_conclusion_date', $visits[0]['review_status']);
        $this->assertEquals($visits[0]['id'], $visits[0]['review_status']['visit_id']);
    }

    public function testGetReviewAvailableVisitFromPatientIdsWithContextAndReviewStatus()
    {
        $patient = $this->populateVisits();
        $visits = $this->visitRepository->getReviewVisitHistoryFromPatientIdsWithContextAndReviewStatus([$patient[0]->id, $patient[1]->id], $patient[0]->study_name);
        $this->assertEquals(6, sizeof($visits));
        $this->assertArrayHasKey('review_status', $visits[0]);
    }

    public function testGetPatientHavingOneAwaitingReviewForUser()
    {
        $patient = $this->populateVisits()[0];
        $answer = $this->visitRepository->getPatientsHavingAtLeastOneAwaitingReviewForUser($patient->study_name, 1, 'ancilary');
        $this->assertEquals(1, sizeof($answer));
    }

    public function testIsParentPatientHavingOneVisitAwaitingReview()
    {
        //create patient with 2 visits
        $patient = Patient::factory()->create();
        $visits = Visit::factory()->patientId($patient->id)->count(2)->create();
        //create review status being available for review
        $visits->each(function ($visit, $key) use ($patient) {
            ReviewStatus::factory()->visitId($visit->id)->reviewAvailable()->studyName($patient->study_name)->create();
        });

        //create one form for user for one visit (patient still has one visit awaiting review for user)

        Review::factory()->visitId($visits->first()->id)->reviewForm()->userId(1)->validated()->studyName($patient->study_name)->create();
        $answer1 = $this->visitRepository->isParentPatientHavingOneVisitAwaitingReview($visits->first()->id, $patient->study_name, 1);

        $this->assertTrue($answer1);
        //create the second form for user as draft (still available)
        $secondReview = Review::factory()->visitId($visits->last()->id)->reviewForm()->userId(1)->studyName($patient->study_name)->create();

        $answer2 = $this->visitRepository->isParentPatientHavingOneVisitAwaitingReview($visits->first()->id, $patient->study_name, 1);
        $this->assertTrue($answer2);

        //Validate the second draft (should be unavailable)
        $secondReview->validated = true;
        $secondReview->save();
        $answer3 = $this->visitRepository->isParentPatientHavingOneVisitAwaitingReview($visits->first()->id, $patient->study_name, 1);
        $this->assertFalse($answer3);
    }
    
}
