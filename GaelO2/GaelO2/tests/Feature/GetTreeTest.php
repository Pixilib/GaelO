<?php

namespace Tests\Feature;

use App\GaelO\Constants\Constants;
use App\Models\Patient;
use App\Models\ReviewStatus;
use App\Models\Study;
use App\Models\Visit;
use App\Models\VisitGroup;
use App\Models\VisitType;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Artisan;
use Laravel\Passport\Passport;
use Tests\AuthorizationTools;
use Tests\TestCase;
use App\Models\User;

class GetTreeTest extends TestCase
{
    use DatabaseMigrations {
        runDatabaseMigrations as baseRunDatabaseMigrations;
    }

    public function runDatabaseMigrations()
    {
        $this->baseRunDatabaseMigrations();
        $this->artisan('db:seed');

        Passport::actingAs(
            User::where('id',1)->first()
        );

    }

    protected function setUp() : void{
        parent::setUp();
        Artisan::call('passport:install');

        $this->study = factory(Study::class)->create();
        $this->visitGroup = factory(VisitGroup::class)->create(['study_name' => $this->study->name]);
        $this->visitType = factory(VisitType::class)->create(['visit_group_id' => $this->visitGroup->id]);
        $this->patient = factory(Patient::class)->create(['study_name' => $this->study->name, 'center_code' => 0]);
        $this->visit = factory(Visit::class)->create([
            'creator_user_id' => 1,
            'patient_code' => $this->patient->code,
            'visit_type_id' => $this->visitType->id
        ]);

        $this->reviewStatus = factory(ReviewStatus::class)->create([
            'visit_id' => $this->visit->id,
            'study_name' => $this->study->name,
        ]);


    }

    public function testGetTree()
    {
        AuthorizationTools::addRoleToUser(1, Constants::ROLE_INVESTIGATOR, $this->study->name);
        $response = $this->get('/api/studies/'.$this->study->name.'/visits-tree?role=Investigator');
        $response->assertStatus(200);
    }


    public function testGetTreeForbiddenNoRole(){
        AuthorizationTools::addRoleToUser(1, Constants::ROLE_INVESTIGATOR, $this->study->name);
        $this->get('/api/studies/'.$this->study->name.'/visits-tree?role=Controller')->assertStatus(403);

    }
}
