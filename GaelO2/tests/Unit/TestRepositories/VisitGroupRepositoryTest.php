<?php

namespace Tests\Unit\TestRepositories;

use App\GaelO\Repositories\VisitGroupRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

use App\Models\Study;
use App\Models\VisitGroup;
use App\Models\VisitType;

class VisitGroupRepositoryTest extends TestCase
{
    private VisitGroupRepository $visitGroupRepository;

    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed');
        $this->visitGroupRepository = new VisitGroupRepository(new VisitGroup());
    }

    public function testCreateRepositoryGroup(){
        $study = Study::factory()->create();
        $createdVisitGroup = $this->visitGroupRepository->createVisitGroup($study->name, 'wb', 'CT');

        $visitGroup = VisitGroup::get();
        $this->assertEquals($createdVisitGroup['id'], $visitGroup->count());
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
        $answer = $this->visitGroupRepository->isExistingVisitGroup($visitGroup->study->name, $visitGroup->name);
        $this->assertTrue($answer);
    }

    public function testDeleteVisitGroup(){
        $visitGroup = VisitGroup::factory()->create();
        $this->visitGroupRepository->delete($visitGroup->id);

        $visitGroup = VisitGroup::get();
        $this->assertEquals(0, $visitGroup->count());
    }




}
