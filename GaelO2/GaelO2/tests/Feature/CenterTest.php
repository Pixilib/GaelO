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

    /*
    public function testAddCenter()
    {
        $payload = [
            'name' => 'newCenter',
            'code' => '2',
            ''

        ];
        $response = $this->post('/api/centers')->content();
        $answer = json_decode($response, true);
        $this->assertEquals(1,sizeof($answer));
    }*/

    public function testModifyCenter(){

    }
}
