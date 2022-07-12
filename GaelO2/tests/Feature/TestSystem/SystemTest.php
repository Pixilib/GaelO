<?php

namespace Tests\Feature\TestPreference;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\AuthorizationTools;

class SystemTest extends TestCase
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

    public function testGetPreferences()
    {
        AuthorizationTools::actAsAdmin(true);
        $answer = $this->json('GET', 'api/system');
        $answer->assertStatus(200);
        $answer->assertJsonStructure(['platformName', 'adminEmail', 'emailReplyTo', 'corporation',
         'url', 'version']);

    }

    public function testGetPreferencesShouldFailNotAdmin(){
        AuthorizationTools::actAsAdmin(false);
        $this->json('GET', 'api/system')->assertStatus(403);
    }
}
