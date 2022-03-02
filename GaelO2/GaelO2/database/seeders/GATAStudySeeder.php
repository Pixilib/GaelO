<?php

namespace Database\Seeders;

use App\Models\DicomStudy;
use App\Models\DicomSeries;
use App\Models\Patient;
use App\Models\ReviewStatus;
use App\Models\Study;
use App\Models\User;
use App\Models\Visit;
use App\Models\VisitGroup;
use App\Models\VisitType;
use App\Models\Review;
use App\Models\Role;
use Illuminate\Database\Seeder;
use App\GaelO\Constants\Constants;

class GATAStudySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->user = User::factory()->email('someone@gaelo.fr')
            ->password('someone')->centerCode(0)->create();

        $this->study = Study::factory()->name('GATA')->code('111')->patientCodeLength(14)->create();


        Role::factory()->userId($this->user->id)->studyName($this->study->name)->roleName('Supervisor')->create();
        Role::factory()->userId($this->user->id)->studyName($this->study->name)->roleName('Monitor')->create();
        Role::factory()->userId($this->user->id)->studyName($this->study->name)->roleName('Investigator')->create();
        Role::factory()->userId($this->user->id)->studyName($this->study->name)->roleName('Reviewer')->create();
        Role::factory()->userId($this->user->id)->studyName($this->study->name)->roleName('Controller')->create();


        $this->patient = Patient::factory()->id(10000000000001)->inclusionStatus('Included')->studyName($this->study->name)
            ->centerCode(0)->create();
        Patient::factory()->id(10000000000002)->inclusionStatus('Included')->studyName($this->study->name)
            ->centerCode(0)->create();
        Patient::factory()->id(10000000000003)->inclusionStatus('Included')->studyName($this->study->name)
            ->centerCode(0)->create();

        $this->visitGroup = VisitGroup::factory()->studyName($this->study->name)->name('FDG')->modality('PT')->create();
        $this->visitType = VisitType::factory()->visitGroupId($this->visitGroup->id)->name('PET0')->order(1)
            ->limitLowDays(-28)->limitUpDays(0)
            ->localFormNeeded()->qcProbability()->reviewProbability(0)->create();
        VisitType::factory()->visitGroupId($this->visitGroup->id)->name('PTD4')->order(2)->optional()
            ->limitLowDays(0)->limitUpDays(168)
            ->localFormNeeded()->qcProbability()->reviewProbability()->create();
        VisitType::factory()->visitGroupId($this->visitGroup->id)->name('PET4')->order(3)
            ->limitLowDays(84)->limitUpDays(82)
            ->localFormNeeded()->qcProbability()->reviewProbability()->create();
        VisitType::factory()->visitGroupId($this->visitGroup->id)->name('PTD8')->order(4)->optional()
            ->limitLowDays(0)->limitUpDays(168)
            ->localFormNeeded()->qcProbability()->reviewProbability()->create();
        VisitType::factory()->visitGroupId($this->visitGroup->id)->name('PET8')->order(5)
            ->limitLowDays(168)->limitUpDays(176)
            ->localFormNeeded()->qcProbability()->reviewProbability(0)->create();

        $this->visit = Visit::factory()->patientId($this->patient->id)
            ->creatorUserId($this->user->id)
            ->uploadDone()->stateQualityControl(Constants::QUALITY_CONTROL_NOT_NEEDED)
            ->stateInvestigatorForm(Constants::INVESTIGATOR_FORM_DONE)
            ->visitTypeId($this->visitType->id)->done()->create();

        $this->dicomStudy = DicomStudy::factory()->visitId($this->visit->id)->userId($this->user->id)->create();
        DicomSeries::factory()->studyInstanceUID($this->dicomStudy->study_uid)->create();

        //Investigator form
        Review::factory()->studyName($this->study->name)->visitId($this->visit->id)->userId($this->user->id)
        ->reviewData([
            "glycaemia" => 13.0,
            "glycaemiaNotDone" => true,
            "radiotherapyThreeMonths" => true,
            "csfThreeWeeks" => true,
            "biopsy" => true,
            "biopsyDate" => "2/6/2022",
            "biopsyLocation" => "Mesenteric",
            "infection" => true,
            "infectionDate" => "6/20/2022",
            "infectionLocation" => "Supraclavicular right",
            "comment" => "truc"
        ])->create();

        //Reviewer form
        ReviewStatus::factory()->studyName($this->study->name)->visitId($this->visit->id)
            ->reviewAvailable()->reviewStatus('Done')->create();
        Review::factory()->studyName($this->study->name)->visitId($this->visit->id)->userId($this->user->id)->reviewForm()
        ->reviewData([
            "lyric" => "NMR"
        ])->create();

    }
}
