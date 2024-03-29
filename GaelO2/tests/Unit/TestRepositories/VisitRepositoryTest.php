<?php

namespace Tests\Unit\TestRepositories;

use App\GaelO\Constants\Constants;
use App\GaelO\Constants\Enums\InvestigatorFormStateEnum;
use App\GaelO\Constants\Enums\QualityControlStateEnum;
use App\GaelO\Constants\Enums\ReviewStatusEnum;
use App\GaelO\Constants\Enums\UploadStatusEnum;
use App\GaelO\Constants\Enums\VisitStatusDoneEnum;
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

class VisitRepositoryTest extends TestCase
{
    private VisitRepository $visitRepository;

    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed');
        $this->visitRepository = new VisitRepository();
    }

    public function testCreateVisit()
    {
        $study = Study::factory()->create();
        $patient = Patient::factory()->create();
        $visitType = VisitType::factory()->create();
        $user = User::factory()->create();

        $visitId = $this->visitRepository->createVisit(
            $study->name,
            $user->id,
            $patient->id,
            null,
            $visitType->id,
            VisitStatusDoneEnum::DONE->value,
            null,
            InvestigatorFormStateEnum::DONE->value,
            QualityControlStateEnum::NOT_DONE->value,
            ReviewStatusEnum::NOT_DONE->value
        );

        $visits = Visit::findOrFail($visitId);
        $this->assertEquals(1, $visits->count());
    }

    public function testIsExistingVisit()
    {
        $visit = Visit::factory()->create();
        $answerExisting = $this->visitRepository->isExistingVisit($visit->patient_id, $visit->visitType->id);
        $visitType = VisitType::factory()->create();
        $answerNotExisting = $this->visitRepository->isExistingVisit($visit->patient_id, $visitType->id);
        $this->assertTrue($answerExisting);
        $this->assertFalse($answerNotExisting);
    }

    public function testUpdateUploadStatus()
    {
        $visit = Visit::factory()->create();
        $this->visitRepository->updateUploadStatus($visit->id, UploadStatusEnum::DONE->value);
        $updatedVisit = Visit::find($visit->id);
        $this->assertEquals(UploadStatusEnum::DONE->value, $updatedVisit->upload_status->value);
    }

    public function testGetVisitContext()
    {

        $visit = Visit::factory()->create();
        $visitContext = $this->visitRepository->getVisitContext($visit->id);
        $this->assertArrayHasKey('visit_type', $visitContext);
        $this->assertArrayHasKey('visit_group', $visitContext['visit_type']);
        $this->assertArrayHasKey('patient', $visitContext);
    }

    public function testGetVisitWithContextAndReviewStatus()
    {
        $reviewStatus = ReviewStatus::factory()->create();
        $visitData = $this->visitRepository->getVisitWithContextAndReviewStatus($reviewStatus->visit_id, $reviewStatus->study_name);
        $this->assertArrayHasKey('visit_type', $visitData);
        $this->assertArrayHasKey('creator', $visitData);
        $this->assertArrayHasKey('review_status', $visitData);
    }

    private function populateVisits()
    {
        $study = Study::factory()->create();
        //Create 2 patient in which we will populate of visits
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
                ReviewStatus::factory()->visitId($visit->id)->reviewAvailable()->studyName($patient->study_name)->create();
                $visit2 = Visit::factory()->visitTypeId($item->id)->uploadDone()->stateInvestigatorForm(InvestigatorFormStateEnum::DONE->value)->stateQualityControl(QualityControlStateEnum::NOT_NEEDED->value)->patientId($patient2->id)->create();
                ReviewStatus::factory()->visitId($visit2->id)->studyName($patient->study_name)->create();
            });
        });

        return [$patient, $patient2];
    }

    public function testGetPatientVisits()
    {

        $patient = $this->populateVisits()[0];

        $visits = $this->visitRepository->getPatientsVisits($patient->id);
        $this->assertEquals(6, sizeof($visits));
    }

    public function testGetPatientVisitsWithReviewStatus()
    {
        $patient = $this->populateVisits()[0];

        $visits = $this->visitRepository->getAllPatientsVisitsWithReviewStatus($patient->id, $patient->study_name, false);
        $this->assertArrayHasKey('review_status', $visits[0]['review_status']);
        $this->assertArrayHasKey('review_available', $visits[0]['review_status']);
        $this->assertArrayHasKey('review_conclusion_value', $visits[0]['review_status']);
        $this->assertArrayHasKey('review_conclusion_date', $visits[0]['review_status']);
        $this->assertEquals($visits[0]['id'], $visits[0]['review_status']['visit_id']);
        $this->assertEquals($patient->study_name, $visits[0]['review_status']['study_name']);
    }

    public function testGetPatientVisitsWithReviewStatusWithTrashed()
    {
        $patient = $this->populateVisits()[0];
        $patient->visits->first()->delete();
        $visits = $this->visitRepository->getAllPatientsVisitsWithReviewStatus($patient->id, $patient->study_name, true);
        $this->assertNotNull($visits[0]['deleted_at']);
    }

    public function testGetPatientListVisitsWithContext()
    {
        $patient = $this->populateVisits();
        $visits = $this->visitRepository->getVisitsFromPatientIdsWithContext([$patient[0]->id, $patient[1]->id]);
        $this->assertEquals(12, sizeof($visits));
        $this->assertArrayHasKey('visit_type', $visits[0]);
        $this->assertArrayHasKey('visit_group', $visits[0]['visit_type']);
    }

    public function testGetPatientListVisitWithContextAndReviewStatus()

    {
        $patient = $this->populateVisits();
        $visits = $this->visitRepository->getVisitFromPatientIdsWithContextAndReviewStatus([$patient[0]->id, $patient[1]->id], $patient[0]->study_name);
        $this->assertEquals(12, sizeof($visits));
        $this->assertArrayHasKey('review_status', $visits[0]);
    }

    public function testGetReviewAvailableVisitFromPatientIdsWithContextAndReviewStatus()
    {
        $patient = $this->populateVisits();
        $visits = $this->visitRepository->getReviewVisitHistoryFromPatientIdsWithContextAndReviewStatus([$patient[0]->id, $patient[1]->id], $patient[0]->study_name);
        $this->assertEquals(6, sizeof($visits));
        $this->assertArrayHasKey('review_status', $visits[0]);
    }

    public function testGetVisitInStudy()
    {
        $patient = $this->populateVisits()[0];

        //Generate data of a second study that should not be selected
        $this->populateVisits()[0];

        $visits = $this->visitRepository->getVisitsInStudy($patient->study_name, false, false, false, null);
        $this->assertEquals(12, sizeof($visits));
        $this->assertArrayHasKey('visit_type', $visits[0]);
        $this->assertArrayHasKey('visit_group', $visits[0]['visit_type']);

        $visitsWithReview = $this->visitRepository->getVisitsInStudy($patient->study_name, true, false, false, null);
        $this->assertEquals(12, sizeof($visitsWithReview));
        $this->assertArrayHasKey('review_status', $visitsWithReview[0]);
        $this->assertEquals($visitsWithReview[0]['id'], $visitsWithReview[0]['review_status']['visit_id']);
        $this->assertEquals($patient->study_name, $visitsWithReview[0]['review_status']['study_name']);
    }

    public function testHasVisitInStudy()
    {
        $study = Study::factory()->create();
        $answer = $this->visitRepository->hasVisitsInStudy($study->name);
        $this->assertFalse($answer);

        $patient = $this->populateVisits()[0];

        //Generate data of a second study that should not be selected
        $this->populateVisits()[0];

        $answer2 = $this->visitRepository->hasVisitsInStudy($patient->study_name);
        $this->assertTrue($answer2);
    }

    public function testGetVisitsInVisitType()
    {
        $visit = Visit::factory()->count(5)->create();
        $answer = $this->visitRepository->getVisitsInVisitType($visit->first()->visitType->id);
        $this->assertEquals(1, sizeof($answer));
    }

    public function testGetVisitsInVisitTypeWithTrashed()
    {
        $visit = Visit::factory()->count(5)->create();
        $visit->first()->delete();
        $answer = $this->visitRepository->getVisitsInVisitType($visit->first()->visitType->id, true, '', true);
        $this->assertEquals(1, sizeof($answer));
        $answer = $this->visitRepository->getVisitsInVisitType($visit->first()->visitType->id, true, '', false);
        $this->assertEquals(0, sizeof($answer));
    }

    public function testGetVisitsInVisitTypeWithReviewStatus()
    {
        $visit = Visit::factory()->count(5)->create();
        $study = Study::factory()->create();
        ReviewStatus::factory()->create([
            'visit_id' => $visit->first()->id,
            'study_name' => $study->name,
            'review_available' => true
        ]);

        $answer = $this->visitRepository->getVisitsInVisitType($visit->first()->visitType->id, true, $study->name);
        $this->assertEquals(1, sizeof($answer));
        $this->assertArrayHasKey('review_status', $answer[0]);
    }



    public function testGetVisitsInStudyAwaitingControllerAction()
    {
        $patient = $this->populateVisits()[0];
        $visits = $patient->visits;
        //Set one of these 12 visits as QC Done
        $visitEntity = $visits->get(0);
        $visitEntity->state_quality_control = QualityControlStateEnum::REFUSED->value;
        $visitEntity->save();
        //Set one of these 12 visits as Awaiting Definitive conclusion (still access for controller)
        $visitEntity = $visits->get(1);
        $visitEntity->state_quality_control = QualityControlStateEnum::WAIT_DEFINITIVE_CONCLUSION->value;
        $visitEntity->save();

        $visits->each(function ($item, $key) {
            $item->state_investigator_form = InvestigatorFormStateEnum::DONE->value;
            $item->upload_status = UploadStatusEnum::DONE->value;
            $item->save();
        });

        //Test
        $visits = $this->visitRepository->getVisitsInStudyAwaitingControllerAction($patient->study_name);

        $this->assertEquals(5, sizeof($visits));
        $this->assertArrayHasKey('visit_type', $visits[0]);
        $this->assertArrayHasKey('visit_group', $visits[0]['visit_type']);
    }

    private function createVisit(bool $reviewAvailable)
    {
        $visit = Visit::factory()->create();

        ReviewStatus::factory()->create([
            'visit_id' => $visit->id,
            'study_name' => $visit->patient->study_name,
            'review_available' => $reviewAvailable
        ]);

        return $visit;
    }

    public function testGetPatientHavingOneAwaitingReviewForUser()
    {
        $patient = $this->populateVisits()[0];
        $studyName = $patient->study->name;
        $answer = $this->visitRepository->getPatientsHavingAtLeastOneAwaitingReviewForUser($studyName, 1, null);
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

    public function testEditQC()
    {
        $visit = Visit::factory()->create();
        $this->visitRepository->editQc($visit->id, QualityControlStateEnum::ACCEPTED->value, 1, true, true, 'OK', 'OK');
        $updatedVisit = Visit::find($visit->id);

        $this->assertEquals(QualityControlStateEnum::ACCEPTED->value, $updatedVisit->state_quality_control->value);
        $this->assertEquals('OK', $updatedVisit->image_quality_comment);
        $this->assertEquals('OK', $updatedVisit->form_quality_comment);
    }

    public function testResetQC()
    {
        $visit = Visit::factory()->stateQualityControl(QualityControlStateEnum::ACCEPTED->value)->create();
        $this->visitRepository->resetQc($visit->id);

        $updatedVisit = Visit::find($visit->id);
        $this->assertEquals(QualityControlStateEnum::NOT_DONE->value, $updatedVisit->state_quality_control->value);
        $this->assertNull($updatedVisit->controller_user_id);
        $this->assertNull($updatedVisit->control_date);
        $this->assertNull($updatedVisit->image_quality_control);
        $this->assertNull($updatedVisit->form_quality_control);
        $this->assertNull($updatedVisit->image_quality_comment);
        $this->assertNull($updatedVisit->form_quality_comment);
        $this->assertNull($updatedVisit->corrective_action_user_id);
        $this->assertNull($updatedVisit->corrective_action_date);
        $this->assertFalse(boolval($updatedVisit->corrective_action_new_upload));
        $this->assertFalse(boolval($updatedVisit->corrective_action_investigator_form));
        $this->assertNull($updatedVisit->corrective_action_comment);
        $this->assertFalse(boolval($updatedVisit->corrective_action_applied));
    }

    public function testSetCorrectiveAction()
    {
        $visit = Visit::factory()->create();
        $this->visitRepository->setCorrectiveAction(
            $visit->id,
            1,
            true,
            true,
            true,
            'updated'
        );

        $updatedVisit = Visit::find($visit->id);
        $this->assertEquals(QualityControlStateEnum::WAIT_DEFINITIVE_CONCLUSION->value, $updatedVisit->state_quality_control->value);
        $this->assertTrue(boolval($updatedVisit->corrective_action_new_upload));
        $this->assertTrue(boolval($updatedVisit->corrective_action_investigator_form));
        $this->assertEquals('updated', $updatedVisit->corrective_action_comment);
    }

    public function testUpdateInvestigatorFormStatus()
    {
        $visit = Visit::factory()->create();
        $this->visitRepository->updateInvestigatorFormStatus($visit->id, InvestigatorFormStateEnum::DRAFT->value);
        $updatedVisit = Visit::find($visit->id);
        $this->assertEquals(InvestigatorFormStateEnum::DRAFT->value, $updatedVisit->state_investigator_form->value);
    }

    public function testImagingVisitAwaitingUpload()
    {

        $patients = $this->populateVisits();
        $visits = $this->visitRepository->getImagingVisitsAwaitingUpload($patients[0]->study->name, [$patients[0]->center_code, $patients[1]->center_code]);
        $this->assertEquals(6, sizeof($visits));
        $this->assertArrayHasKey('patient', $visits[0]);
    }

    public function testDeleteVisit()
    {
        $visit = Visit::factory()->create();

        $this->visitRepository->delete($visit->id);

        $this->expectException(ModelNotFoundException::class);
        Visit::findOrFail($visit->id);
    }

    public function testReactivateVisit()
    {
        $visit = Visit::factory()->create();
        $visit->delete();

        $this->visitRepository->reactivateVisit($visit->id);

        $updatedVisit = Visit::findOrFail($visit->id);
        $this->assertEquals(1, $updatedVisit->count());
    }

    public function testGetVisitContextByVisitIdArray()
    {
        $visits = Visit::factory()->count(5)->create();
        $visitIdArray = $visits->pluck('id');
        $results = $this->visitRepository->getVisitContextByVisitIdArray($visitIdArray->toArray());
        $this->assertEquals(5, sizeof($results));
    }

    public function testUpdateVisitDate()
    {
        $visit = Visit::factory()->create();
        $originalVisitDate = $visit['visit_date'];

        $this->visitRepository->updateVisitDate($visit->id, now()->addDay());

        $updatedVisit = Visit::findOrFail($visit->id);
        $this->assertNotEquals($updatedVisit['visit_date'], $originalVisitDate);
    }

    public function testGetVisitsInStudyNeedingQualityControl()
    {
        $patient = Patient::factory()->create();
        Visit::factory()->patientId($patient->id)->stateInvestigatorForm(InvestigatorFormStateEnum::DONE->value)->uploadDone()->count(5)->create();
        //Create Visit with requested status but not same study
        Visit::factory()->stateInvestigatorForm(InvestigatorFormStateEnum::DONE->value)->uploadDone()->count(3)->create();
        //Create Visit from same study but uncorrect status
        Visit::factory()->patientId($patient->id)->stateInvestigatorForm(InvestigatorFormStateEnum::DONE->value)->count(5)->create();
        Visit::factory()->patientId($patient->id)->uploadDone()->count(5)->create();
        $answers = $this->visitRepository->getVisitsInStudyNeedingQualityControl($patient->study_name);
        $this->assertEquals(5, sizeof($answers));
    }

    public function testGetVisitOfPatientByVisitTypeName()
    {
        $visits = Visit::factory()->count(5)->create();
        $visit = $visits->get(3);
        $visitTypeName = $visit->visitType->name;
        $visitGroupName = $visit->visitType->visitGroup->name;
        $patientId = $visit->patient_id;
        $studyName = $visit->patient->study_name;
        //Should find the correct visit
        $foundVisit = $this->visitRepository->getVisitOfPatientByVisitTypeName($patientId, $visitGroupName, $visitTypeName, true, $studyName);
        $this->assertEquals($visit->id, $foundVisit['id']);
        //Should throw exception as does not exist
        $this->expectException(ModelNotFoundException::class);
        $this->visitRepository->getVisitOfPatientByVisitTypeName($patientId, $visitGroupName, $visitTypeName.'wrong', true, $studyName);
    }

    public function testUpdateVisitFiles(){
        $visit = Visit::factory()->create();
        $this->visitRepository->updateVisitFile($visit->id, ['myKey' => 'myFile.pdf'] );
        $updatedVisit = Visit::find($visit->id);
        $this->assertArrayHasKey('myKey', $updatedVisit['sent_files']);
    }
}
