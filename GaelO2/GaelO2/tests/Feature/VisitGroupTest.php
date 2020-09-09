<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Artisan;
use Laravel\Passport\Passport;
use Tests\TestCase;
use App\User;
use App\Study;

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

    protected function setUp() : void{
        parent::setUp();

        Artisan::call('passport:install');
        Passport::actingAs(
            User::where('id',1)->first()
        );

        $this->study = factory(Study::class, 1)->create();

    }

    public function testCreateVisitGroup()
    {
        $payload = [
            'modality' => 'CT'
        ];
        $study = $this->study->first()->toArray();
        $response = $this->post('api/studies/'.$study['name'].'/visit-groups', $payload);
        dd($response);
        $response->assertStatus(200);
        //SK RESTE A CHECKER L ENREGISTREMENT EN BDD
    }
}
