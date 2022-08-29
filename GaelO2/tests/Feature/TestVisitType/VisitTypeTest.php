<?php

namespace Tests\Feature\TestVisitType;

use App\Models\Patient;
use App\Models\Study;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;
use App\Models\VisitGroup;
use App\Models\VisitType;
use App\Models\Visit;
use Tests\AuthorizationTools;

class VisitTypeTest extends TestCase
{
    use DatabaseMigrations {
        runDatabaseMigrations as baseRunDatabaseMigrations;
    }

    /**
     * Define hooks to migrate the database before and after each test.
     *
     * @return void
     */
    public function runDatabaseMigrations()
    {
        $this->baseRunDatabaseMigrations();
        $this->artisan('db:seed');
    }

    protected function setUp() : void {

        parent::setUp();

        $this->visitGroup = VisitGroup::factory()->create();

        $this->payload = [
            'name'=>'Baseline',
            'order'=>0,
            'localFormNeeded'=>true,
            'qcProbability'=>100,
            'reviewProbability'=>100,
            'optional'=>true,
            'limitLowDays'=>5,
            'limitUpDays'=>50,
            'anonProfile'=>'Default',
            'dicomConstraints'=>[]
        ];

    }

    public function testCreateVisitType()
    {
        AuthorizationTools::actAsAdmin(true);
        $id = $this->visitGroup->id;
        $this->json('POST', 'api/visit-groups/'.$id.'/visit-types', $this->payload)->assertNoContent(201);
        $visitType = VisitType::where('name', 'Baseline')->get()->first();
        $this->assertEquals(14, sizeOf($visitType->toArray()));
        $this->assertEquals($this->payload['qcProbability'], $visitType->qc_probability);
        $this->assertEquals($this->payload['reviewProbability'], $visitType->review_probability);
    }

    public function testCreateVisitTypeShouldFailedBecauseAlreadyExistingName()
    {
        AuthorizationTools::actAsAdmin(true);
        $visitType = VisitType::factory()->create();

        $payload = $this->payload;
        $payload['name'] = $visitType['name'];

        $this->json('POST', 'api/visit-groups/'.$visitType->visitGroup->id.'/visit-types', $payload)->assertStatus(409);
    }

    public function testCreateVisitTypeShouldFailedBecauseAlreadyExistingVisitsInStudy()
    {
        AuthorizationTools::actAsAdmin(true);
        $study = Study::factory()->create();
        $patient = Patient::factory()->studyName($study->name)->create();
        $visitGroup = VisitGroup::factory()->studyName($study->name)->create();
        $visitType = VisitType::factory()->visitGroupId($visitGroup->id)->create();
        $visit = Visit::factory()->patientId($patient->id)->visitTypeId($visitType->id)->create();

        $payload = $this->payload;
        //Sk ici a eu un random fail sur un conflict, a surveiller
        $this->json('POST', 'api/visit-groups/'.$visit->visitType->visitGroup->id.'/visit-types', $payload)->assertStatus(403);
    }

    public function testCreateVisitTypeForbiddenNotAdmin()
    {
        AuthorizationTools::actAsAdmin(false);
        $visitType = VisitType::factory()->create();
        $id = $visitType->visitGroup->id;
        $this->json('POST', 'api/visit-groups/'.$id.'/visit-types', $this->payload)->assertStatus(403);
    }

    public function testGetVisitType(){

        AuthorizationTools::actAsAdmin(true);
        $visitType = VisitType::factory()->create();

        $answer = $this->json('GET', 'api/visit-types/'.$visitType->id);
        $answer->assertStatus(200);

        $expectedKeys = [
            "id",
            "visitGroupId",
            "name",
            "order",
            "localFormNeeded",
            "qcProbability",
            "reviewProbability",
            "optional",
            "limitLowDays",
            "limitUpDays",
            "anonProfile",
            "dicomConstraints"
        ];

        $answer->assertJsonStructure($expectedKeys);

    }

    public function testGetVisitTypeForbiddenNotAdmin()
    {
        AuthorizationTools::actAsAdmin(false);
        $visitType = VisitType::factory()->create();

        $this->json('GET', 'api/visit-types/'.$visitType->id)->assertStatus(403);
    }

    public function testDeleteVisitType(){
        AuthorizationTools::actAsAdmin(true);
        $visitGroup = VisitGroup::factory()->create();
        $visitType = VisitType::factory()->visitGroupId($visitGroup->id)->create();
        $this->json('DELETE', 'api/visit-types/'.$visitType->id)->assertStatus(200);
    }

    public function testDeleteVisitTypeForbiddenNotAdmin(){
        AuthorizationTools::actAsAdmin(false);
        $visitGroup = VisitGroup::factory()->create();
        $visitType = VisitType::factory()->visitGroupId($visitGroup->id)->create();
        $this->json('DELETE', 'api/visit-types/'.$visitType->id)->assertStatus(403);
    }

    public function testDeleteVisitTypeShouldFailedBecauseHasChildVisit(){
        AuthorizationTools::actAsAdmin(true);
        $patient = Patient::factory()->create();
        $visitGroup = VisitGroup::factory()->studyName($patient->study_name)->create();
        $visitType = VisitType::factory()->visitGroupId($visitGroup->id)->create();
        $visits = Visit::factory()->patientId($patient->id)->visitTypeId($visitType->id)->create();
        $this->json('DELETE', 'api/visit-types/'.$visits->first()->visitType->id)->assertStatus(403);
    }
}
