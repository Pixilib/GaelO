<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Laravel\Passport\Passport;
use Tests\TestCase;
use App\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class ReverseProxyTest extends TestCase
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

        if (true) {
            $this->markTestSkipped('all tests in this file are invactive, this is only to check orthanc communication');
        }
    }

    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function testDicomWebReverseProxy()
    {

        $response = $this->get('/api/orthanc/dicom-web/studies/1.2.826.0.1.3680043.5.2014.6.4.10.27.08.20160309120853.168.11008/metadata', ['Accept'=>'application/json']);
        $response->assertStatus(200);
    }

    public function testTusReverseProxy()
    {

        $response = $this->get('/api/tus');
        $response->assertStatus(200);
    }
}
