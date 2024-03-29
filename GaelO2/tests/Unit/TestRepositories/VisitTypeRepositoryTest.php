<?php

namespace Tests\Unit\TestRepositories;

use App\GaelO\Constants\Enums\AnonProfileEnum;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

use App\GaelO\Repositories\VisitTypeRepository;
use App\Models\VisitGroup;
use App\Models\VisitType;

class VisitTypeRepositoryTest extends TestCase
{
    private VisitTypeRepository $visitTypeRepository;

    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed');
        $this->visitTypeRepository = new VisitTypeRepository(new VisitType());
    }

    public function testCreateVisitType(){
        $visitGroup = VisitGroup::factory()->create();
        $this->visitTypeRepository->createVisitType($visitGroup->id, 'PET_0', 0, true, 100, 100, false, 0, 100, AnonProfileEnum::DEFAULT->value, []);

        $visitType = VisitType::get()->first();

        $this->assertEquals('PET_0', $visitType->name);
        $this->assertEquals(100, $visitType->limit_up_days);

    }

    public function testIsExistingVisitType(){
        $visitType = VisitType::factory()->create();

        $answer = $this->visitTypeRepository->isExistingVisitType($visitType->visitGroup->id , $visitType->name);
        $answer2 = $this->visitTypeRepository->isExistingVisitType($visitType->visitGroup->id , 'New Visit Type');
        $this->assertTrue($answer);
        $this->assertFalse($answer2);
    }

    public function testIsExistingOrder(){
        $visitType = VisitType::factory()->order(5)->create();

        $answer = $this->visitTypeRepository->isExistingOrder($visitType->visitGroup->id , $visitType->order);
        $answer2 = $this->visitTypeRepository->isExistingOrder($visitType->visitGroup->id , 0);
        $this->assertTrue($answer);
        $this->assertFalse($answer2);
    }

    public function testDeleteVisitType(){

        $visitType = VisitType::factory()->create();
        $this->visitTypeRepository->delete($visitType->id);

        $visitType = VisitType::get();

        $this->assertEquals(0, sizeOf($visitType));


    }

    public function testGetVisitTypeByName(){
        $visitType1 = VisitType::factory()->create();
        VisitType::factory()->create();

        $visitTypeEntity = $this->visitTypeRepository->findByName($visitType1->visitGroup->study_name, $visitType1->visitGroup->name, $visitType1->name);

        $this->assertIsArray($visitTypeEntity);
    }

    public function testGetVisitTypeOfStudy(){

        $visitGroup = VisitGroup::factory()->create();
        VisitType::factory()->visitGroupId($visitGroup->id)->count(2)->create();

        VisitType::factory()->count(5)->create();

        $results = $this->visitTypeRepository->getVisitTypesOfStudy($visitGroup->study_name);

        $this->assertEquals(2, sizeof($results));
        $this->assertArrayHasKey('visit_group', $results[0]);
    }

}
