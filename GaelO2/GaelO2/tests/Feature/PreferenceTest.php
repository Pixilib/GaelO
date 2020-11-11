<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Artisan;
use Laravel\Passport\Passport;
use App\User;

class PreferenceTest extends TestCase
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

    public function testGetPreferences()
    {
        $answer = $this->json('GET', 'api/preferences');
        $answer->assertStatus(200);
        $answer->assertJsonStructure(['platformName', 'adminEmail', 'emailReplyTo', 'corporation',
         'url', 'patientCodeLength']);

    }
}
