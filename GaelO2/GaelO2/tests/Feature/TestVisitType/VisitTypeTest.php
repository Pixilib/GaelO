<?php

namespace Tests\Feature\TestVisitType;

use App\GaelO\UseCases\GetVisitType\VisitTypeEntity;
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
            'visitOrder'=>0,
            'localFormNeeded'=>true,
            'qcNeeded'=>true,
            'reviewNeeded'=>true,
            'optional'=>true,
            'limitLowDays'=>5,
            'limitUpDays'=>50,
            'anonProfile'=>'Default'
        ];

    }

    public function testCreateVisitType()
    {
        AuthorizationTools::actAsAdmin(true);
        $id = $this->visitGroup->id;
        $this->json('POST', 'api/visit-groups/'.$id.'/visit-types', $this->payload)->assertNoContent(201);
        $visitGroup = VisitType::where('name', 'Baseline')->get()->first()->toArray();
        $this->assertEquals(13, sizeOf($visitGroup));
    }

    public function testCreateVisitTypeShouldFailedBecauseAlreadyExistingName()
    {
        AuthorizationTools::actAsAdmin(true);
        $visitType = VisitType::factory()->create();

        $payload = $this->payload;
        $payload['name'] = $visitType['name'];

        $this->json('POST', 'api/visit-groups/'.$visitType->visitGroup->id.'/visit-types', $payload)->assertStatus(409);
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

        $response = $this->json('GET', 'api/visit-types/'.$visitType->id)->content();
        $response = json_decode($response, true);
        //Check that all value in output entity is in response
        foreach ( get_class_vars(VisitTypeEntity::class) as $key=>$value ){
            $this->assertArrayHasKey($key, $response);
        }

    }

    public function testGetVisitTypeForbiddenNotAdmin()
    {
        AuthorizationTools::actAsAdmin(false);
        $visitType = VisitType::factory()->create();

        $this->json('GET', 'api/visit-types/'.$visitType->id)->assertStatus(403);
    }

    public function testDeleteVisitType(){
        AuthorizationTools::actAsAdmin(true);
        $visitType = VisitType::factory()->create();
        $this->json('DELETE', 'api/visit-types/'.$visitType->id)->assertStatus(200);
    }

    public function testDeleteVisitTypeForbiddenNotAdmin(){
        AuthorizationTools::actAsAdmin(false);
        $visitType = VisitType::factory()->create();
        $this->json('DELETE', 'api/visit-types/'.$visitType->id)->assertStatus(403);
    }

    public function testDeleteVisitTypeShouldFailedBecauseHasChildVisit(){
        AuthorizationTools::actAsAdmin(true);
        $visit = Visit::factory()->create();

        $this->json('DELETE', 'api/visit-types/'.$visit->visitType->id)->assertStatus(409);
    }
}
