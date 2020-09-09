<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Artisan;
use Laravel\Passport\Passport;
use Tests\TestCase;
use App\User;

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
        Passport::actingAs(
            User::where('id',1)->first()
        );
    }

    public function testGetCenter()
    {
        $response = $this->get('/api/centers')->content();
        $answer = json_decode($response, true);
        $this->assertEquals(1,sizeof($answer));
    }


    public function testAddCenter()
    {
        $payload = [
            'name' => 'Paris',
            'code' => 8,
            'countryCode'=>'US'

        ];
        $this->post('/api/centers', $payload)->assertNoContent(201);
    }

    public function testModifyCenter(){

        $payload = [
            'name' => 'newCenter',
            'countryCode'=>'FR'

        ];
        $this->put('/api/centers/0', $payload)->assertNoContent(200);


    }

    public function testModifyCenterNotExisting(){

        $payload = [
            'name' => 'newCenter',
            'countryCode'=>'FR'

        ];
        //Non existing center modification should fail
        $this->put('/api/centers/1', $payload)->assertNoContent(400);

    }
}
