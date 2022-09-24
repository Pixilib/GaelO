<?php

namespace Tests\Feature\TestVisitGroup;

use App\Models\Patient;

use Tests\TestCase;
use App\Models\Study;
use App\Models\Visit;
use App\Models\VisitGroup;
use App\Models\VisitType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\AuthorizationTools;

class VisitGroupTest extends TestCase
{

    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed');
    }


    public function testGetVisitGroup(){
        AuthorizationTools::actAsAdmin(true);
        $visitGroup = VisitGroup::factory()->create();
        $response = $this->json('GET', 'api/visit-groups/'.$visitGroup->id)->content();
        $response = json_decode($response, true);
        //Check all Item in visitGroupEntity are present in reponse
        foreach ( ['id', 'modality', 'name', 'studyName'] as $key ){
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
            'name' => 'wb',
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
            'name' => 'wb',
            'modality' => 'CT'
        ];
        $study = Study::factory()->create();
        $this->json('POST', 'api/studies/'.$study['name'].'/visit-groups', $payload)->assertStatus(403);

    }

    public function testDeleteVisitGroupShouldFailBecauseExistingVisits(){
        AuthorizationTools::actAsAdmin(true);
        $visitType = VisitType::factory()->create();
        $this->json('DELETE', 'api/visit-groups/'.$visitType->visitGroup->id)->assertStatus(403);
    }

    public function testCreateVisitGroupShouldFailBecauseExistingVisits(){
        AuthorizationTools::actAsAdmin(true);
        $study = Study::factory()->create();
        $patient = Patient::factory()->studyName($study->name)->create();
        Visit::factory()->patientId($patient->id)->create();
        $payload = [
            'name' => 'wb',
            'modality' => 'PT'
        ];
        $this->json('POST', 'api/studies/'.$study->name.'/visit-groups', $payload)->assertStatus(403);
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
