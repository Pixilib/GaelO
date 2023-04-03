<?php

namespace Tests\Unit\TestRepositories;

use App\GaelO\Repositories\CenterRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

use App\Models\Center;
use App\Models\Patient;
use App\Models\User;

class CenterRepositoryTest extends TestCase
{
    private CenterRepository $centerRepository;

    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed');
        $this->centerRepository = new CenterRepository(new Center());

        //Create 2 random Centers
        $this->center3 = Center::factory()->code(3)->create();
        $this->center5 = Center::factory()->code(5)->create();
    }

    public function testCreateCenter(){

        $this->centerRepository->createCenter(10, 'testing', 'FR');
        $createdCenter = Center::find(10)->toArray();
        $this->assertNotNull($createdCenter);

    }

    public function testIsExistingCenterName(){
        $answer = $this->centerRepository->isExistingCenterName($this->center3->name);
        $this->assertTrue($answer);
    }

    public function testGetCenterByCode(){
        $centerEntity = $this->centerRepository->getCenterByCode($this->center3->code);
        $this->assertEquals($centerEntity['name'], $this->center3->name);
    }

    public function testIsKnownCenter(){
        $isKnown = $this->centerRepository->isKnownCenter($this->center3->code);
        $this->assertTrue($isKnown);
        $isKnown = $this->centerRepository->isKnownCenter(8);
        $this->assertFalse($isKnown);
    }

    public function testUpdateCenter(){

        $this->centerRepository->updateCenter($this->center3->code, 'newCenterName', 'TN');
        $updatedCenter = Center::findOrFail(3)->toArray();
        $this->assertEquals($updatedCenter['name'], 'newCenterName');
        $this->assertEquals($updatedCenter['country_code'], 'TN');

    }

    public function testGetCentersFromCodeArray() {
        $centerCodesArray = [$this->center3->code, $this->center5->code];
        $centerEntitiesArray = $this->centerRepository->getCentersFromCodeArray($centerCodesArray);
        $fetchedCentersCodes = array_column($centerEntitiesArray, 'code');
        $this->assertTrue(!array_diff($fetchedCentersCodes, $centerCodesArray));
    }

    public function testGetPatientsFromCenter(){
        $centerCode = $this->center3->code;
        Patient::factory()->centerCode($centerCode)->count(3)->create();
        $patients = $this->centerRepository->getPatientsOfCenter($centerCode);
        $this->assertEquals(3, sizeof($patients));
    }

    public function testGetUsersFromCenter(){
        $centerCode = $this->center3->code;
        User::factory()->centerCode($centerCode)->count(5)->create();
        $patients = $this->centerRepository->getUsersOfCenter($centerCode);
        $this->assertEquals(5, sizeof($patients));
    }
}
