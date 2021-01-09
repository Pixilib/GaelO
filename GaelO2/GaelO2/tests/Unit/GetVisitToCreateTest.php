<?php

namespace Tests\Unit;

use App\GaelO\Constants\Constants;
use App\Model\Patient;
use App\Model\ReviewStatus;
use App\Model\Study;
use App\Model\VisitGroup;
use App\Model\VisitType;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Tests\TestCase;
use App\Model\Visit;

class GetVisitToCreateTest extends TestCase
{

    use DatabaseMigrations {
        runDatabaseMigrations as baseRunDatabaseMigrations;
    }

    use RefreshDatabase;

    public function runDatabaseMigrations()
    {
        $this->baseRunDatabaseMigrations();
        $this->artisan('db:seed');
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->study = factory(Study::class)->create(['patient_code_prefix' => 1234]);

        $this->patient = factory(Patient::class)->create(['study_name' => $this->study->name, 'inclusion_status'=>Constants::PATIENT_INCLUSION_STATUS_INCLUDED, 'center_code' => 0]);

        $this->visitGroupPT = factory(VisitGroup::class)->create(['modality'=>'PT', 'study_name' => $this->study->name]);
        $this->visitGroupCT = factory(VisitGroup::class)->create(['modality'=>'CT', 'study_name' => $this->study->name]);

        $this->visitType = factory(VisitType::class, 3)->create(
            [
                'visit_group_id' => $this->visitGroupPT->id,
            ]
        );

        $this->visitType = factory(VisitType::class, 2)->create(
            [
                'visit_group_id' => $this->visitGroupCT->id,
            ]
        );


        $this->visitService  = App::Make(\App\GaelO\Services\VisitService::class);
    }


    public function testGetVisitToCreateNoCreatedVisit(){


        //$this->createVisit(Constants::INVESTIGATOR_FORM_DONE, true, true, true);
        $visitToCreate = $this->visitService->getAvailableVisitToCreate($this->patient->code );
        $this->assertEquals(2, sizeof($visitToCreate['CT']));
        $this->assertEquals(3, sizeof($visitToCreate['PT']));
    }

    public function testGetVisitToCreateEmptyBecauseNotIncluded(){
        $this->patient['inclusion_status'] = Constants::PATIENT_INCLUSION_STATUS_WITHDRAWN;
        $this->patient->save();
        $visitToCreate = $this->visitService->getAvailableVisitToCreate($this->patient->code );
        $this->assertEquals(0, sizeof($visitToCreate));
    }

    public function testGetVisitToCreateExistingCreatedVisit(){

        //Make One visit created in the CT Group
        $visit = factory(Visit::class)->create(
            [
                'creator_user_id' => 1,
                'patient_code' => $this->patient->code,
                'visit_type_id' => $this->visitType->first()->id,
            ]
        );

        $this->reviewStatus = factory(ReviewStatus::class)->create(
            [
                'visit_id' => $visit->id,
                'study_name' => $this->study->name
            ]
        );

        $visitToCreate = $this->visitService->getAvailableVisitToCreate($this->patient->code );
        $this->assertEquals(1, sizeof($visitToCreate['CT']));
        $this->assertEquals(3, sizeof($visitToCreate['PT']));


    }


    /*

    */

}
