<?php

namespace Tests\Feature\TestSystem;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\AuthorizationTools;

class SystemTest extends TestCase
{

    use RefreshDatabase;

    protected function setUp() : void{
        parent::setUp();
        $this->artisan('db:seed');
    }

    public function testGetPreferences()
    {
        AuthorizationTools::actAsAdmin(true);
        $answer = $this->json('GET', 'api/system');
        $answer->assertStatus(200);
        $answer->assertJsonStructure(['platformName', 'mailFromAddress', 'mailReplyTo', 'corporation',
         'url', 'version']);

    }

    public function testGetPreferencesShouldFailNotAdmin(){
        AuthorizationTools::actAsAdmin(false);
        $this->json('GET', 'api/system')->assertStatus(403);
    }
}
