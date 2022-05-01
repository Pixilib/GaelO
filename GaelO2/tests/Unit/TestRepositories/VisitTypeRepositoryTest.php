<?php

namespace Tests\Unit\TestRepositories;

use App\GaelO\Constants\Constants;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

use App\GaelO\Repositories\VisitTypeRepository;
use App\Models\Visit;
use App\Models\VisitGroup;
use App\Models\VisitType;

class VisitTypeRepositoryTest extends TestCase
{
    private VisitTypeRepository $visitTypeRepository;

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
        $this->visitTypeRepository = new VisitTypeRepository(new VisitType());
    }

    public function testCreateVisitType(){
        $visitGroup = VisitGroup::factory()->create();
        $this->visitTypeRepository->createVisitType($visitGroup->id, 'PET_0', 0, true, 100, 100, false, 0, 100, Constants::ORTHANC_ANON_PROFILE_DEFAULT, []);

        $visitType = VisitType::get()->first();

        $this->assertEquals('PET_0', $visitType->name);
        $this->assertEquals(100, $visitType->limit_up_days);

    }

    public function testHasVisit(){
        $visitType = VisitType::factory()->create();

        $answer = $this->visitTypeRepository->hasVisits($visitType->id);

        $visit = Visit::factory()->create();
        $visitTypeWithVisit = $visit->visitType;
        $answer2 = $this->visitTypeRepository->hasVisits($visitTypeWithVisit->id);

        $this->assertTrue($answer2);
        $this->assertFalse($answer);
    }

    public function testIsExistingVisitType(){
        $visitType = VisitType::factory()->create();

        $answer = $this->visitTypeRepository->isExistingVisitType($visitType->visitGroup->id , $visitType->name);
        $answer2 = $this->visitTypeRepository->isExistingVisitType($visitType->visitGroup->id , 'New Visit Type');
        $this->assertTrue($answer);
        $this->assertFalse($answer2);
    }

    public function testDeleteVisitType(){

        $visitType = VisitType::factory()->create();
        $this->visitTypeRepository->delete($visitType->id);

        $visitType = VisitType::get();

        $this->assertEquals(0, sizeOf($visitType));


    }

    public function testGetVisitTypesFromIdArray() {
        $visitType1 = VisitType::factory()->create();
        $visitType2 = VisitType::factory()->create();

        $visitTypeIds = [$visitType1->id, $visitType2->id];
        $visitTypeEntitiesArray = $this->visitTypeRepository->getVisitTypesFromIdArray($visitTypeIds);
        $fetchedVisitTypes = array_column($visitTypeEntitiesArray, 'id');
        $this->assertTrue(!array_diff($fetchedVisitTypes, $visitTypeIds));

    }

    public function testGetVisitTypeByName(){
        $visitType1 = VisitType::factory()->create();
        VisitType::factory()->create();

        $visitTypeEntity = $this->visitTypeRepository->findByName($visitType1->visitGroup->study_name, $visitType1->visitGroup->name, $visitType1->name);

        $this->assertIsArray($visitTypeEntity);
    }

}
