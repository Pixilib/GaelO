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

class FrontTest extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->call([
            CountrySeeder::class,
            CenterSeeder::class,
            GATAStudySeeder::class
        ]);

        $this->user = User::factory()->administrator()->email('administrator@gaelo.fr')
            ->password('administrator')->centerCode(0)->create();

        $this->study = Study::factory()->name('TEST')->code('123')->patientCodeLength(5)->create();

        foreach (Study::all() as $studyEntity) {
            Role::factory()->userId($this->user->id)->studyName($studyEntity->name)->roleName('Supervisor')->create();
            Role::factory()->userId($this->user->id)->studyName($studyEntity->name)->roleName('Monitor')->create();
            Role::factory()->userId($this->user->id)->studyName($studyEntity->name)->roleName('Investigator')->create();
            Role::factory()->userId($this->user->id)->studyName($studyEntity->name)->roleName('Reviewer')->create();
            Role::factory()->userId($this->user->id)->studyName($studyEntity->name)->roleName('Controller')->create();
        }

        $this->patient = Patient::factory()->id(rand(10000, 99999))->inclusionStatus('Included')->investigatorName('administrator')
            ->studyName($this->study->name)->centerCode(0)->create();
        Patient::factory()->id(rand(10000, 99999))->investigatorName('administrator')
            ->studyName($this->study->name)->centerCode(0)->create();
        Patient::factory()->id(rand(10000, 99999))->investigatorName('administrator')
            ->studyName($this->study->name)->centerCode(0)->create();

        $this->visitGroup = VisitGroup::factory()->studyName($this->study->name)->create();
        VisitType::factory()->count(6)->visitGroupId($this->visitGroup->id)->limitLowDays(0)->limitUpDays(5)->create();

        $this->visitGroup = VisitGroup::factory()->studyName($this->study->name)->name('PT')->modality('PT')->create();

        $this->visitType = VisitType::factory()->name('PET0')->visitGroupId($this->visitGroup->id)
            ->localFormNeeded()->qcProbability()->reviewProbability()->limitLowDays(1)->limitUpDays(2)->create();

        $this->visit = Visit::factory()->creatorUserId($this->user->id)->patientId($this->patient->id)
            ->uploadDone()->stateQualityControl(Constants::QUALITY_CONTROL_NOT_NEEDED)
            ->stateInvestigatorForm(Constants::INVESTIGATOR_FORM_DONE)
            ->visitTypeId($this->visitType->id)->done()->create();

        $this->dicomStudy = DicomStudy::factory()->visitId($this->visit->id)->userId($this->user->id)->create();
        DicomSeries::factory()->studyInstanceUID($this->dicomStudy->study_uid)->create();

        Review::factory()->studyName($this->study->name)->visitId($this->visit->id)->userId($this->user->id)->create();

        ReviewStatus::factory()->studyName($this->study->name)->visitId($this->visit->id)
            ->reviewAvailable()->reviewStatus('Done')->create();
        Review::factory()->studyName($this->study->name)->visitId($this->visit->id)->userId($this->user->id)->reviewForm()->create();
    }
}
