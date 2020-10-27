<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Artisan;
use Laravel\Passport\Passport;
use Tests\TestCase;
use App\User;
use App\Center;
use App\CenterUser;

class AffiliatedCenterTest extends TestCase
{

    use DatabaseMigrations {
        runDatabaseMigrations as baseRunDatabaseMigrations;
    }

    public function runDatabaseMigrations() {
        $this->baseRunDatabaseMigrations();
        $this->artisan('db:seed');
    }

    protected function setUp() : void{
        parent::setUp();
        factory(Center::class)->create(['code'=>3]);
        factory(Center::class)->create(['code'=>4]);

        Artisan::call('passport:install');
        Passport::actingAs(
            User::where('id',1)->first()
        );

    }

    public function testCreateAffiliatedCenterToUser()
    {
        $payload = [
            'centerCode' => 3
        ];
        $this->json('POST', 'api/users/1/affiliated-centers', $payload)->assertNoContent(201);

        $affiliatedCenter =User::where('id',1)->first()->affiliatedCenters()->get()->toArray();
        $this->assertEquals(sizeof($affiliatedCenter), 1);
        $this->assertEquals($affiliatedCenter[0]['code'], 3);
    }

    public function testCreateAlreadyExistingAffiliatedCenterToUser(){

        $payload = [
            'centerCode' => 3
        ];
        $this->json('POST', 'api/users/1/affiliated-centers', $payload)->assertNoContent(201);

        $affiliatedCenter =User::where('id',1)->first()->affiliatedCenters()->get()->toArray();
        $this->assertEquals(sizeof($affiliatedCenter), 1);

    }

    public function testGetAffiliatedCenterOfUser(){
        factory(CenterUser::class)->create(['user_id'=>1, 'center_code'=>3]);
        $response = $this->json('GET', 'api/users/1/affiliated-centers')->assertSuccessful();
        $response = json_decode($response->content(), true);
        $this->assertEquals(sizeof($response), 1);

    }

    public function testDeleteAffiliatedCenterOfUser(){
        factory(CenterUser::class)->create(['user_id'=>1, 'center_code'=>3]);
        $this->json('DELETE', 'api/users/1/affiliated-centers/3')->assertNoContent(200);
        $databaseData = CenterUser::where(['user_id'=>1])->get();
        $this->assertEquals(sizeof($databaseData->toArray()), 0);
    }

    public function testDeleteAffiliatedCenterOfUserShouldFailBecauseNotExistingAffiliatedCenter(){
        $this->json('DELETE', 'api/users/1/affiliated-centers/3')->assertStatus(404);
    }
}
