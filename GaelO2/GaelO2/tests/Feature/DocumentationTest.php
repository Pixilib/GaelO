<?php

namespace Tests\Feature;

use App\GaelO\Constants\Constants;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Artisan;
use Laravel\Passport\Passport;
use Tests\TestCase;
use App\User;
use App\Study;
use Tests\AuthorizationTools;

class DocumentationTest extends TestCase
{

    use DatabaseMigrations {
        runDatabaseMigrations as baseRunDatabaseMigrations;
    }

    public function runDatabaseMigrations()
    {
        $this->baseRunDatabaseMigrations();
        $this->artisan('db:seed');
    }

    protected function setUp() : void{
        parent::setUp();
        $this->study = factory(Study::class, 1)->create(['name'=> 'test', 'patient_code_prefix' => 1234])->first();

        $this->validPayload = [
            'name'=>'documentationTest',
            'version'=>'1.1.0',
            'investigator'=>true,
            'monitor'=>true,
            'controller'=>false,
            'reviewer'=>false
        ];

        Artisan::call('passport:install');
        Passport::actingAs(
            User::where('id',1)->first()
        );
    }

    public function testForbiddenWhenNotSupervisor(){
        $response = $this->post('api/studies/'.$this->study->name.'/documentation', $this->validPayload);
        $response->assertStatus(403);
    }

    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function testCreateDocumentation()
    {
        AuthorizationTools::addRoleToUser(1, Constants::ROLE_SUPERVISOR, $this->study->name);
        $response = $this->post('api/studies/'.$this->study->name.'/documentation', $this->validPayload);
        $response->assertStatus(201);
        $response->assertJsonStructure(['id']);
    }
}
