<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
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
        $this->get('api/preferences')->assertStatus(200);
    }

    public function testPutPreferences(){

        $payload = [
            'patientCodeLength'=>15,
            'parseDateImport'=>'m.d.Y',
            'parseCountryName'=>'FR'
        ];

        $this->put('api/preferences', $payload)->assertNoContent(200);

        $content = $this->get('api/preferences')->content();
        $newPreferenceArray = json_decode($content, true);
        $this->assertEquals(15, $newPreferenceArray['patientCodeLength']);
        $this->assertEquals('m.d.Y', $newPreferenceArray['parseDateImport']);
        $this->assertEquals('FR', $newPreferenceArray['parseCountryName']);
    }
}
