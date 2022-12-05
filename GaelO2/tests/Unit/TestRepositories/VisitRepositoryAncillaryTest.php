<?php

namespace Tests\Unit\TestRepositories;

use App\GaelO\Constants\Constants;
use App\GaelO\Repositories\VisitRepository;
use App\Models\Patient;
use App\Models\Review;
use App\Models\ReviewStatus;
use App\Models\Study;
use Tests\TestCase;
use App\Models\Visit;
use App\Models\VisitGroup;
use App\Models\VisitType;
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
        $this->originalStudyName = $study->name;
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
                ReviewStatus::factory()->visitId($visit->id)->reviewAvailable()->reviewStatus(Constants::REVIEW_STATUS_NOT_NEEDED)->studyName($patient->study_name)->create();
                $visit2 = Visit::factory()->visitTypeId($item->id)->uploadDone()->stateInvestigatorForm(Constants::INVESTIGATOR_FORM_DONE)->stateQualityControl(Constants::QUALITY_CONTROL_NOT_NEEDED)->patientId($patient2->id)->create();
                ReviewStatus::factory()->visitId($visit2->id)->studyName($patient->study_name)->create();
            });
        });

        $ancillaryStudy = Study::factory()->ancillaryOf($study->name)->create();
        $this->ancillaryStudyName = $ancillaryStudy->name;

        return [$patient, $patient2];
    }

    public function testGetPatientVisitsWithDefaultReviewStatus()
    {
        $patient = $this->populateVisits()[0];

        $ancillaryvisits = $this->visitRepository->getAllPatientsVisitsWithReviewStatus($patient->id, $this->ancillaryStudyName, false);
        $originalvisits = $this->visitRepository->getAllPatientsVisitsWithReviewStatus($patient->id, $this->originalStudyName, false);
        $this->assertArrayHasKey('review_status', $ancillaryvisits[0]['review_status']);
        $this->assertArrayHasKey('review_available', $ancillaryvisits[0]['review_status']);
        $this->assertArrayHasKey('review_conclusion_value', $ancillaryvisits[0]['review_status']);
        $this->assertArrayHasKey('review_conclusion_date', $ancillaryvisits[0]['review_status']);
        $this->assertEquals($ancillaryvisits[0]['id'], $ancillaryvisits[0]['review_status']['visit_id']);
        //review status depend on ancillary study
        $this->assertNotEquals($ancillaryvisits[0]['review_status']['review_available'], $originalvisits[0]['review_status']['review_available']);
        $this->assertNotEquals($ancillaryvisits[0]['review_status']['review_status'], $originalvisits[0]['review_status']['review_status']);
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
