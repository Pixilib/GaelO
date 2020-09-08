<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Artisan;
use Laravel\Passport\Passport;
use Tests\TestCase;
use App\User;

class StudyTest extends TestCase
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

    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function testCreateStudy()
    {
        $payload = [
            'studyName'=>'NewStudy',
            'patientCodePreffix'=>'1234'
        ];
        $response = $this->post('/api/studies', $payload)->assertNoContent(201);
        $response = $this->post('/api/studies', $payload)->assertNoContent(409);
        //SK CHECKER QUE L ENREGISTEMENT EST BIEN DANS LA TABLE
    }
}
