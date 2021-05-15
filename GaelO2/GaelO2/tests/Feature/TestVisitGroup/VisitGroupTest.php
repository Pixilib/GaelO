<?php

namespace Tests\Feature\TestVisitGroup;

use App\GaelO\Entities\VisitGroupEntity;
use Illuminate\Foundation\Testing\DatabaseMigrations;

use Tests\TestCase;
use App\Models\Study;
use App\Models\Visit;
use App\Models\VisitGroup;
use App\Models\VisitType;
use Tests\AuthorizationTools;

class VisitGroupTest extends TestCase
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


    public function testGetVisitGroup(){
        AuthorizationTools::actAsAdmin(true);
        $visitGroup = VisitGroup::factory()->create();
        $response = $this->json('GET', 'api/visit-groups/'.$visitGroup->id)->content();
        $response = json_decode($response, true);
        //Check all Item in visitGroupEntity are present in reponse
        foreach ( get_class_vars(VisitGroupEntity::class) as $key=>$value ){
            $this->assertArrayHasKey($key, $response);
        }

    }

    public function testGetVisitGroupForbiddenNotAdmin(){
        AuthorizationTools::actAsAdmin(false);
        $visitGroup = VisitGroup::factory()->create();
        $this->json('GET', 'api/visit-groups/'.$visitGroup->id)->assertStatus(403);
    }


    public function testCreateVisitGroup() {
        AuthorizationTools::actAsAdmin(true);
        $payload = [
            'modality' => 'CT'
        ];
        $study = Study::factory()->create();
        $this->json('POST', 'api/studies/'.$study['name'].'/visit-groups', $payload)->assertStatus(201);
        //Check record in database
        $visitGroup = VisitGroup::where('study_name', $study['name'])->get()->first()->toArray();
        $this->assertEquals('CT', $visitGroup['modality']);
    }

    public function testCreateVisitGroupForbiddenNotAdmin(){
        AuthorizationTools::actAsAdmin(false);
        $payload = [
            'modality' => 'CT'
        ];
        $study = Study::factory()->create();
        $this->json('POST', 'api/studies/'.$study['name'].'/visit-groups', $payload)->assertStatus(403);

    }

    public function testDeleteVisitGroupShouldFailBecauseExistingVisitTypes(){
        AuthorizationTools::actAsAdmin(true);
        $visitType = VisitType::factory()->create();
        $this->json('DELETE', 'api/visit-groups/'.$visitType->visitGroup->id)->assertStatus(400);
    }

    public function testCreateVisitGroupShouldFailBecauseExistingVisits(){
        AuthorizationTools::actAsAdmin(true);
        $visit = Visit::factory()->create();
        $studyName = $visit->visitType->visitGroup->study->name;
        $payload = [
            'modality' => 'PT'
        ];
        $this->json('POST', 'api/studies/'.$studyName.'/visit-groups', $payload)->assertStatus(403);
    }

    public function testDeleteVisitGroup(){
        AuthorizationTools::actAsAdmin(true);
        $visitGroup = VisitGroup::factory()->create();
        $this->json('DELETE', 'api/visit-groups/'.$visitGroup->id)->assertStatus(200);
    }

    public function testDeleteVisitGroupForbiddenNotAdmin(){
        AuthorizationTools::actAsAdmin(false);
        $visitGroup = VisitGroup::factory()->create();
        $this->json('DELETE', 'api/visit-groups/'.$visitGroup->id)->assertStatus(403);
    }

}
