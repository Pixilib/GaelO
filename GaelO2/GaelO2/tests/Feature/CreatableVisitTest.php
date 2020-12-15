<?php

namespace Tests\Feature;

use App\GaelO\Constants\Constants;
use App\Patient;
use App\Study;
use App\User;
use App\VisitGroup;
use App\VisitType;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Laravel\Passport\Passport;
use Tests\AuthorizationTools;
use Tests\TestCase;

class CreatableVisitTest extends TestCase
{
    use DatabaseMigrations {
        runDatabaseMigrations as baseRunDatabaseMigrations;
    }

    use RefreshDatabase;

    public function runDatabaseMigrations()
    {
        $this->baseRunDatabaseMigrations();
        $this->artisan('db:seed');
    }

    protected function setUp(): void
    {
        parent::setUp();

        Artisan::call('passport:install');

        Passport::actingAs(
            User::where('id',1)->first()
        );

        $this->study = factory(Study::class)->create(['patient_code_prefix' => 1234]);
        $this->patient = factory(Patient::class)->create(['study_name' => $this->study->name, 'inclusion_status'=>Constants::PATIENT_INCLUSION_STATUS_INCLUDED, 'center_code' => 0]);
        $this->visitGroupPT = factory(VisitGroup::class)->create(['modality'=>'PT', 'study_name' => $this->study->name]);
        $this->visitType = factory(VisitType::class)->create(
            [
                'visit_group_id' => $this->visitGroupPT->id,
            ]
        );

    }

    public function testGetCreatableVisit()
    {
        AuthorizationTools::addRoleToUser(1, Constants::ROLE_INVESTIGATOR, $this->study->name);
        $response = $this->get('/api/studies/'.$this->study->name.'/patients/'.$this->patient->code.'/creatable-visits');
        $response->assertStatus(200);
    }

    public function testGetCreatableVisitShouldFailNoRole()
    {
        $response = $this->get('/api/studies/'.$this->study->name.'/patients/'.$this->patient->code.'/creatable-visits');
        $response->assertStatus(403);
    }
}
