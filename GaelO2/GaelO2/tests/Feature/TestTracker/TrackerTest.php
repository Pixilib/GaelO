<?php

namespace Tests\Feature\TrackerTest;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

use Tests\AuthorizationTools;

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

    public function testGetTracker () {
        AuthorizationTools::actAsAdmin(true);
        //Test that tracker routes work properly
        $this->json('GET', '/api/tracker?admin=false')->assertSuccessful();
        $this->json('GET', '/api/tracker?admin=true')->assertSuccessful();
    }

    public function testGetTrackerForbiddenNotAdmin(){
        //To be changed when supervisor implemented
        AuthorizationTools::actAsAdmin(false);
        $this->json('GET', '/api/tracker?admin=false')->assertStatus(403);
    }

}
