<?php

namespace Tests\Unit\TestRepositories;

use App\GaelO\Constants\Constants;
use App\GaelO\Repositories\VisitGroupRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

use App\GaelO\Repositories\VisitTypeRepository;
use App\Models\Study;
use App\Models\VisitGroup;
use App\Models\VisitType;

class VisitGroupRepositoryTest extends TestCase
{
    private VisitGroupRepository $visitGroupRepository;

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
        $this->visitGroupRepository = new VisitGroupRepository(new VisitGroup());
    }

    public function testCreateRepositoryGroup(){
        $study = Study::factory()->create();
        $this->visitGroupRepository->createVisitGroup($study->name, 'CT');

        $visitGroup = VisitGroup::get();
        $this->assertEquals(1, $visitGroup->count());
    }

    public function testHasVisitType(){
        $visitGroup = VisitGroup::factory()->create();
        $answer = $this->visitGroupRepository->hasVisitTypes($visitGroup->id);

        $visitType = VisitType::factory()->create();
        $answer2 = $this->visitGroupRepository->hasVisitTypes($visitType->visitGroup->id);

        $this->assertTrue($answer2);
        $this->assertFalse($answer);
    }

    public function testIsExistingVisitGroup(){
        $visitGroup = VisitGroup::factory()->create();
        $answer = $this->visitGroupRepository->isExistingVisitGroup($visitGroup->study->name, $visitGroup->modality);
        $this->assertTrue($answer);
    }

    public function testDeleteVisitGroup(){
        $visitGroup = VisitGroup::factory()->create();
        $this->visitGroupRepository->delete($visitGroup->id);

        $visitGroup = VisitGroup::get();
        $this->assertEquals(0, $visitGroup->count());
    }




}
