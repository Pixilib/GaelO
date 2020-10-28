<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Artisan;
use Laravel\Passport\Passport;
use Tests\TestCase;
use App\User;
use Illuminate\Support\Facades\DB;

use function GuzzleHttp\json_encode;

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
        factory(User::class, 1)->create(['administrator' => false]);
        DB::table('trackers')->insert([
            'study_name' => null,
            'user_id' => 2,
            'date' => now(),
            'role' => 'User',
            'visit_id' => null,
            'action_type' => 'Something',
            'action_details' => json_encode(array('a'=> 'b', 'c' => 'd'))
        ]);

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
        DB::table('trackers')->insert([
            'study_name' => null,
            'user_id' => 1,
            'date' => now(),
            'role' => 'Administrator',
            'visit_id' => null,
            'action_type' => 'Something',
            'action_details' => json_encode(array('a'=> 'b', 'c' => 'd'))
        ]);
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
