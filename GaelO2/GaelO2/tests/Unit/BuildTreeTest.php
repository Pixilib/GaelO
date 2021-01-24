<?php

namespace Tests\Unit;

use App\GaelO\Constants\Constants;
use App\GaelO\Services\VisitTreeService;
use App\Models\Patient;
use App\Models\Review;
use App\Models\ReviewStatus;
use App\Models\Study;
use App\Models\Visit;
use App\Models\VisitGroup;
use App\Models\VisitType;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Tests\TestCase;

class BuildTreeTest extends TestCase
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

        $this->study = factory(Study::class)->create();
        $this->visitGroup = factory(VisitGroup::class)->create(['study_name' => $this->study->name]);
        $this->visitType = factory(VisitType::class)->create(['visit_group_id' => $this->visitGroup->id]);
        $this->patient = factory(Patient::class)->create(['study_name' => $this->study->name, 'center_code' => 0]);
        $this->visit = factory(Visit::class)->create([
            'creator_user_id' => 1,
            'patient_code' => $this->patient->code,
            'visit_type_id' => $this->visitType->id
        ]);

        $this->reviewStatus = factory(ReviewStatus::class)->create([
            'visit_id' => $this->visit->id,
            'study_name' => $this->study->name,
            'review_available'=>true
        ]);

        $this->treeService = App::make(VisitTreeService::class);
    }

    public function testTreeMonitor()
    {

        $this->treeService->setUserAndStudy(1, Constants::ROLE_MONITOR, $this->study->name);
        //dd($this->treeService->buildTree());

    }

    public function testTreeController()
    {

        $this->treeService->setUserAndStudy(1, Constants::ROLE_CONTROLLER, $this->study->name);
        //dd($this->treeService->buildTree());

    }

    public function testTreeInvestigator()
    {

        $this->treeService->setUserAndStudy(1, Constants::ROLE_INVESTIGATOR, $this->study->name);
        //dd($this->treeService->buildTree());

    }

    public function testTreeReviewer(){

        factory(Review::class)->create([
            'visit_id' => $this->visit->id,
            'study_name' => $this->study->name,
            'user_id'=>1,
            'validated'=>true
        ]);

        $this->treeService->setUserAndStudy(1, Constants::ROLE_REVIEWER, $this->study->name);
        //dd($this->treeService->buildTree());
    }
}
