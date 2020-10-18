<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Artisan;
use Laravel\Passport\Passport;
use Tests\TestCase;
use App\User;
use App\Study;
use App\Role;
use Illuminate\Support\Facades\Log;
use App\Tracker;

class TrackerTest extends TestCase
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

        Artisan::call('passport:install');
        Passport::actingAs(
            User::where('id',1)->first()
        );
    }

    public function testGetTracker () {
        //Test that tracker routes work properly
        $this->json('GET', '/api/tracker?admin=false')->assertSuccessful();
        $this->json('GET', '/api/tracker?admin=true')->assertSuccessful();
    }

    public function testGetUserTracker()
    {
        //Manually create user so that the associated tracker can be created
        $this->validPayload =
        ['username' => 'truc',
        'lastname' => 'truc',
        'firstname' => 'truc',
        'email' => 'truc@truc.fr',
        'phone' => '0600000000',
        'administrator' => true,
        'centerCode' => 0,
        'job' => 'Monitor',
        'orthancAddress' => 'test',
        'orthancLogin' => 'test',
        'orthancPassword' => 'test'];
        $resp = $this->json('POST', '/api/users', $this->validPayload);

        //Test that admin tracker is empty
        $content = $this->json('GET', '/api/tracker?admin=true')->content();
        $content = json_decode($content, true);
        $this->assertEquals(0, sizeof($content));

        //Test that user tracker has 1 line
        $content = $this->json('GET', '/api/tracker?admin=false')->content();
        $content = json_decode($content, true);
        $this->assertEquals(1, sizeof($content));
    }

    public function testGetAdminTracker() {
        //Create 2 random studies
        $studies = factory(Study::class, 1)->create();

        $studyName = $studies->first()['name'];
        $payload = ["Investigator", "Supervisor"];
        $this->json('POST', '/api/users/1/roles/'.$studyName, $payload);

        //Test that user tracker is empty
        $content = $this->json('GET', '/api/tracker?admin=false')->content();
        $content = json_decode($content, true);
        $this->assertEquals(0, sizeof($content));

        //Test that admin tracker has 1 line
        $content = $this->json('GET', '/api/tracker?admin=true')->content();
        $content = json_decode($content, true);
        $this->assertEquals(1, sizeof($content));
    }

}
