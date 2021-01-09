<?php

namespace Tests\Feature;

use App\GaelO\UseCases\GetVisitGroup\VisitGroupEntity;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Artisan;
use Laravel\Passport\Passport;
use Tests\TestCase;
use App\Models\User;
use App\Models\Study;
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

    protected function setUp() : void {
        parent::setUp();

        Artisan::call('passport:install');
        Passport::actingAs(
            User::where('id',1)->first()
        );

        $this->study = factory(Study::class, 2)->create();


    }

    public function testGetVisitGroup(){

        $visitGroup = factory(VisitGroup::class, 1)->create(['study_name'=> $this->study[0]->name]);
        $response = $this->json('GET', 'api/visit-groups/'.$visitGroup[0]->id)->content();
        $response = json_decode($response, true);
        //Check all Item in visitGroupEntity are present in reponse
        foreach ( get_class_vars(VisitGroupEntity::class) as $key=>$value ){
            $this->assertArrayHasKey($key, $response);
        }

    }

    public function testGetVisitGroupForbiddenNotAdmin(){
        AuthorizationTools::actAsAdmin(false);
        $visitGroup = factory(VisitGroup::class, 1)->create(['study_name'=> $this->study[0]->name]);
        $this->json('GET', 'api/visit-groups/'.$visitGroup[0]->id)->assertStatus(403);
    }


    public function testCreateVisitGroup() {
        $payload = [
            'modality' => 'CT'
        ];
        $study = $this->study->first()->toArray();
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
        $study = $this->study->first()->toArray();
        $this->json('POST', 'api/studies/'.$study['name'].'/visit-groups', $payload)->assertStatus(403);

    }

    public function testDeleteVisitGroupShouldFailBecauseExistingVisitTypes(){
        $visitGroup = factory(VisitGroup::class, 1)->create(['study_name'=> $this->study[0]->name]);
        $visitGroup->each(function ($visitGroup) {
            factory(VisitType::class)->create(['visit_group_id'=>$visitGroup->id]);
        });
        $this->json('DELETE', 'api/visit-groups/'.$visitGroup[0]->id)->assertStatus(400);
    }

    public function testDeleteVisitGroup(){
        $visitGroup = factory(VisitGroup::class, 1)->create(['study_name'=> $this->study[0]->name]);
        $this->json('DELETE', 'api/visit-groups/'.$visitGroup[0]->id)->assertStatus(200);
    }

    public function testDeleteVisitGroupForbiddenNotAdmin(){
        AuthorizationTools::actAsAdmin(false);
        $visitGroup = factory(VisitGroup::class, 1)->create(['study_name'=> $this->study[0]->name]);
        $this->json('DELETE', 'api/visit-groups/'.$visitGroup[0]->id)->assertStatus(403);
    }

}
