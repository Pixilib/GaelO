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
        $this->json('GET', 'api/preferences')->assertStatus(200);
    }

    public function testPutPreferences(){

        $payload = [
            'patientCodeLength'=>15,
            'parseDateImport'=>'d.m.Y',
            'parseCountryName'=>'FR'
        ];

        $this->json('PUT', 'api/preferences', $payload);

        $content = $this->get('api/preferences')->content();
        $newPreferenceArray = json_decode($content, true);
        $this->assertEquals(14, $newPreferenceArray['patientCodeLength']);
        $this->assertEquals('m.d.Y', $newPreferenceArray['parseDateImport']);
        $this->assertEquals('US', $newPreferenceArray['parseCountryName']);
    }
}
