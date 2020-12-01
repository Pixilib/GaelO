<?php

namespace Tests\Feature;

use App\Center;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Artisan;
use Laravel\Passport\Passport;
use Tests\TestCase;
use App\User;
use Tests\AuthorizationTools;

class CenterTest extends TestCase
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
    }



    public function testGetCenters()
    {
        AuthorizationTools::actAsAdmin(true);
        $response = $this->json('GET', '/api/centers')->content();
        $answer = json_decode($response, true);
        $this->assertEquals(1,sizeof($answer));
    }

    public function testGetCentersShouldFailNotAdmin(){
        AuthorizationTools::actAsAdmin(false);
        $this->json('GET', '/api/centers')->assertStatus(403);
    }

    public function testGetCenter()
    {
        AuthorizationTools::actAsAdmin(true);
        $center = factory(Center::class)->create();
        $response = $this->json('GET', '/api/centers/'.$center->code)->content();
        $answer = json_decode($response, true);
        $this->assertArrayHasKey('code', $answer);
        $this->assertArrayHasKey('name', $answer);
        $this->assertArrayHasKey('countryCode', $answer);
    }

    public function testGetCenterShouldFailNotAdmin(){
        AuthorizationTools::actAsAdmin(false);
        $center = factory(Center::class)->create();
        $this->json('GET', '/api/centers/'.$center->code)->assertStatus(403);
    }


    public function testAddCenter()
    {
        AuthorizationTools::actAsAdmin(true);
        $payload = [
            'name' => 'Paris',
            'code' => 8,
            'countryCode'=>'US'

        ];
        $this->json('POST', '/api/centers', $payload)->assertStatus(201);
    }

    public function testAddCenterShouldFailNotAdmin()
    {
        AuthorizationTools::actAsAdmin(false);
        $payload = [
            'name' => 'Paris',
            'code' => 8,
            'countryCode'=>'US'

        ];
        $this->json('POST', '/api/centers', $payload)->assertStatus(403);
    }

    public function testAddCenterExistingCode()
    {
        AuthorizationTools::actAsAdmin(true);
        factory(Center::class)->create(
            [
                'code'=>8
            ]
        );

        $payload = [
            'name' => 'Toulouse',
            'code' => 8,
            'countryCode'=>'US'
        ];
        $answer = $this->json('POST', '/api/centers', $payload);
        $answer->assertStatus(409);
        $answer->assertJsonStructure(["errorMessage"]);
    }

    public function testAddCenterExistingName()
    {
        AuthorizationTools::actAsAdmin(true);
        factory(Center::class)->create(
            [
                'code'=>8,
                'name' => 'Paris',
                'country_code'=>'US'
            ]
        );

        $payload = [
            'name' => 'Paris',
            'code' => 9,
            'countryCode'=>'US'
        ];
        $answer = $this->json('POST', '/api/centers', $payload);
        $answer->assertStatus(409);
        $answer->assertJsonStructure(['errorMessage']);
    }

    public function testModifyCenter(){
        AuthorizationTools::actAsAdmin(true);
        $payload = [
            'name' => 'newCenter',
            'countryCode'=>'FR'
        ];
        $answer = $this->json('PUT', '/api/centers/0', $payload);
        $answer->assertStatus(200);
    }

    public function testModifyCenterShouldFailNotAdmin(){
        AuthorizationTools::actAsAdmin(false);
        $payload = [
            'name' => 'newCenter',
            'countryCode'=>'FR'

        ];
        $answer = $this->json('PUT', '/api/centers/0', $payload);
        $answer->assertStatus(403);
    }

    public function testModifyCenterNotExisting(){
        AuthorizationTools::actAsAdmin(true);
        $payload = [
            'name' => 'newCenter',
            'countryCode'=>'FR'
        ];
        //Non existing center modification should fail
        $this->json('PUT', '/api/centers/1', $payload)->assertStatus(404);

    }

    public function testModifyCenterExistingName()
    {
        AuthorizationTools::actAsAdmin(true);

        factory(Center::class)->create([
            'name' => 'Paris',
            'code' => 8,
            'country_code'=>'US'
        ]);
        factory(Center::class)->create([
            'name' => 'Toulouse',
            'code' => 9,
            'country_code'=>'US'
        ]);

        $payload = [
            'name' => 'Toulouse',
            'countryCode'=>'US'
        ];
        $this->json('PUT', '/api/centers/8', $payload)->assertStatus(409);
    }
}
