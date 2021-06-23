<?php

namespace Database\Seeders;

use App\Models\Center;
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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

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
            CenterSeeder::class
        ]);

        $this->user = User::factory()->administrator()->username('administrator')->password('administrator')
            ->centerCode(0)->create();
        
        Study::factory()->count(5)->create();

        $this->study = Study::factory()->name('TEST')->patientCodePrefix('123')->create();

        Role::factory()->userId($this->user->id)->studyName($this->study->name)->roleName('Supervisor')->create();
        Role::factory()->userId($this->user->id)->studyName($this->study->name)->roleName('Monitor')->create();
        Role::factory()->userId($this->user->id)->studyName($this->study->name)->roleName('Investigator')->create();
        Role::factory()->userId($this->user->id)->studyName($this->study->name)->roleName('Reviewer')->create();
        Role::factory()->userId($this->user->id)->studyName($this->study->name)->roleName('Controller')->create();

        Patient::factory()->code(123000 + rand(0,999))->inclusionStatus('Included')
            ->investigatorName('administrator')->studyName($this->study->name)->centerCode(0)->create();
        
        Patient::factory()->count(4)->investigatorName('administrator')
            ->studyName($this->study->name)->centerCode(0)->create();

        $this->visitGroup = VisitGroup::factory()->studyName($this->study->name)->create();
        VisitType::factory()->count(6)->visitGroupId($this->visitGroup['id'])->create();

        $this->visitGroup = VisitGroup::factory()->studyName($this->study->name)->modality('PT')->create();

        $this->visitType = VisitType::factory()->name('PET0')->visitGroupId($this->visitGroup['id'])
            ->localFormNeeded()->qcNeeded()->reviewNeeded()->create();

        $this->visit = Visit::factory()->creatorUserId(1)->patientCode(Patient::first()['code'])
            ->visitTypeId($this->visitType['id'])->done()->create();

        ReviewStatus::factory()->studyName($this->study->name)->visitId($this->visit->id)
            ->reviewAvailable()->reviewStatus('Done')->create();
        Review::factory()->studyName($this->study->name)->visitId($this->visit->id)->reviewForm()->create();

        User::factory()->count(20)->create();
    }
}
