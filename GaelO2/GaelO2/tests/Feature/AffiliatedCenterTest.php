<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Artisan;
use Laravel\Passport\Passport;
use Tests\TestCase;
use App\User;
use App\Center;
use App\CenterUser;
use Tests\AuthorizationTools;

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

    }

    public function testCreateAffiliatedAccessShouldFailForNotAdmin(){
        AuthorizationTools::actAsAdmin(false);

        $payload = [
            'centerCode' => 3
        ];
        $this->json('POST', 'api/users/1/affiliated-centers', $payload)->assertStatus(403);

    }

    public function testGetAffiliatedAccessShouldFailForNotAdmin(){
        AuthorizationTools::actAsAdmin(false);
        $user = factory(User::class)->create(['administrator'=>false]);
        Passport::actingAs(
            User::where('id',$user->id)->first()
        );
        $this->json('GET', 'api/users/1/affiliated-centers')->assertStatus(403);

    }

    public function testDeleteAffiliatedCenterShouldFailForNotAdmin(){
        AuthorizationTools::actAsAdmin(false);
        $user = factory(User::class)->create(['administrator'=>false]);
        Passport::actingAs(
            User::where('id',$user->id)->first()
        );
        factory(CenterUser::class)->create(['user_id'=>1, 'center_code'=>3]);
        $this->json('DELETE', 'api/users/1/affiliated-centers/3')->assertStatus(403);
    }

    public function testCreateAffiliatedCenterToUser()
    {
        AuthorizationTools::actAsAdmin(true);
        $payload = [
            'centerCode' => 3
        ];
        $this->json('POST', 'api/users/1/affiliated-centers', $payload)->assertStatus(201);

        $affiliatedCenter =User::where('id',1)->first()->affiliatedCenters()->get()->toArray();
        $this->assertEquals(sizeof($affiliatedCenter), 1);
        $this->assertEquals($affiliatedCenter[0]['code'], 3);
    }

    public function testCreateAlreadyExistingAffiliatedCenterToUser(){

        AuthorizationTools::actAsAdmin(true);

        factory(CenterUser::class)->create(['user_id'=>1, 'center_code'=>3]);
        $payload = [
            'centerCode' => 3
        ];
        $this->json('POST', 'api/users/1/affiliated-centers', $payload)->assertStatus(409);

        $affiliatedCenter =User::where('id',1)->first()->affiliatedCenters()->get()->toArray();
        $this->assertEquals(sizeof($affiliatedCenter), 1);

    }

    public function testGetAffiliatedCenterOfUser(){
        AuthorizationTools::actAsAdmin(true);
        factory(CenterUser::class)->create(['user_id'=>1, 'center_code'=>3]);
        $response = $this->json('GET', 'api/users/1/affiliated-centers')->assertSuccessful();
        $response = json_decode($response->content(), true);
        $this->assertEquals(sizeof($response), 1);

    }

    public function testDeleteAffiliatedCenterOfUser(){
        AuthorizationTools::actAsAdmin(true);
        factory(CenterUser::class)->create(['user_id'=>1, 'center_code'=>3]);
        $this->json('DELETE', 'api/users/1/affiliated-centers/3')->assertNoContent(200);
        $databaseData = CenterUser::where(['user_id'=>1])->get()->toArray();
        $this->assertEquals(sizeof($databaseData), 0);
    }

    public function testDeleteAffiliatedCenterOfUserShouldFailBecauseNotExistingAffiliatedCenter(){
        AuthorizationTools::actAsAdmin(true);
        $this->json('DELETE', 'api/users/1/affiliated-centers/3')->assertStatus(404);
    }
}
