<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Artisan;
use Laravel\Passport\Passport;
use Tests\TestCase;
use App\User;
use App\VisitGroup;
use App\Study;
use App\VisitType;

class VisitTypeTest extends TestCase
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

    protected function setUp() : void {
        parent::setUp();

        Artisan::call('passport:install');
        Passport::actingAs(
            User::where('id',1)->first()
        );

        $this->study = factory(Study::class, 1)->create();

        $this->visitGroup = factory(VisitGroup::class)->create([
            'study_name' => $this->study->first()->name
        ]);



    }

    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function testCreateVisitType()
    {

        $payload = [
            'name'=>'Baseline',
            'visitOrder'=>0,
            'localFormNeeded'=>true,
            'qcNeeded'=>true,
            'reviewNeeded'=>true,
            'optional'=>true,
            'limitLowDays'=>5,
            'limitUpDays'=>50,
            'anonProfile'=>'Default'
        ];

        $id = $this->visitGroup->id;
        $this->json('POST', 'api/visit-groups/'.$id.'/visit-types', $payload)->assertNoContent(201);
        $visitGroup = VisitType::where('name', 'Baseline')->get()->first()->toArray();
        $this->assertEquals(13, sizeOf($visitGroup));
    }

    public function testGetVisitType(){

        factory(VisitType::class)->create([
            'visit_group_id' => $this->visitGroup->id
        ]);

        $response = $this->json('GET', 'api/visit-groups/'.$this->visitGroup->id.'/visit-types');
        dd($response);
        //->assertNoContent(201);


    }
}
